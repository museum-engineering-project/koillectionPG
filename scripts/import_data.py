#!/usr/bin/env python3

import argparse
import ast
import datetime
import importlib.util
import ipaddress
import json
import random
import re
import subprocess
import sys
import uuid

try:
    import mysql.connector
    from mysql.connector.connection import MySQLCursor
    import pandas as pd
    import yaml
except ImportError as e:
    print(e)
    print()
    print("The following modules are required:")
    print("mysql-connector-python")
    print("pandas")
    print("PyYAML")
    print("You can install them with: pip install {module name}")
    sys.exit(1)

if importlib.util.find_spec("xlrd") is None:
    print()
    print("The xlrd module was not found. Importing Excel files will not work (only CSV).")
    print("You can install it with: pip install xlrd\n")


DEFAULT_COLLECTION_VISIBILITY = "public"  # available options: "public", "private", "internal"
DEFAULT_NAME_COLUMN = "Nazwa"
DEFAULT_ITEM_NAME = "ITEM NAME NOT FOUND"

MAX_SHORT_TEXT_LENGTH = 64  # max length (characters) of short text ("text") fields. Columns having at least one value
                            # longer than that will be imported as long text ("textarea")

COLORS = ["E3F2FD", "F3E5F5", "FBE9E7", "EEEEEE", "E8EAF6"]
# NOTE: Currently Koillection accepts only these 5 colors.
#       Their list can be changed in src/Service/ColorPicker.php and in assets/styles/main.css


def get_current_time() -> str:
    """Returns the current time in YYYY-MM-DD-hh:mm:ss format"""
    return datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')


def get_database_host(container_name: str) -> str:
    """
    Tries to automatically find the database host ip by container name.
    :param container_name: name of the container to find ip address of
    :return: ip address of the container
    """
    try:
        host = subprocess.run(
            "sudo docker inspect -f '{{range.NetworkSettings.Networks}}{{.IPAddress}}{{end}}' " + container_name,
            shell=True, check=True, capture_output=True, encoding="utf-8"
        ).stdout

        ipaddress.ip_address(host.strip())
    except (ValueError, subprocess.CalledProcessError) as e:
        print(e)
        print()
        print("Could not automatically determine the database host IP address.")
        print("Please provide host with the --host argument.")
        sys.exit(1)

    return host


def generate_id() -> str:
    """
    Generates and returns a unique id
    """
    return str(uuid.uuid4())


def load_data(path: str, sheet_name: str, skip_empty_columns: bool, skip_empty_fields: bool) -> tuple[list[dict], list[str], dict[str]]:
    """
    Loads data from the provided Excel file. Returns a list containing dictionaries of each loaded item,
    a list of the file's headers, and a dictionary specifying the types of headers' fields (text or textarea)
    :param path: path to the Excel file
    :param sheet_name: name of the Excel sheet to load data from
    :param skip_empty_columns: whether or not empty columns should be omitted from the final result
    :param skip_empty_fields: whether or not empty fields should be omitted from the final result (works per row)
    :return: list containing dictionaries of each loaded item,
        a list of the file's headers, and a dictionary specifying the types of headers' fields (text or textarea)
    """
    if sheet_name is None:
        sheet_name = 0  # load first sheet if sheet_name is not provided

    try:
        df = pd.read_excel(path, sheet_name=sheet_name, dtype=str)
    except ValueError:
        df = pd.read_csv(path, dtype=str)

    df = df.loc[:, ~df.columns.str.contains('^Unnamed')]  # drop unnamed columns

    duplicate_columns = [col for col in df.columns if re.search(r'\.[0-9]+$', col)]
    df = df.drop(columns=duplicate_columns)
    # drop columns with duplicate names (pandas automatically adds .1 .2 .3, etc. suffix to columns with the same names)

    headers = list(df)

    header_types = {}
    for header in headers:
        max_length = df[header].str.len().max()
        if max_length is not None and max_length > MAX_SHORT_TEXT_LENGTH:
            header_types[header] = "textarea"
        else:
            header_types[header] = "text"

    # drop all rows following the first empty row
    empty_rows = df.isnull().all(axis=1)
    try:
        rows_to_drop = list(range(list(empty_rows).index(True), len(empty_rows)))
        df = df.drop(index=rows_to_drop)
    except ValueError:
        pass  # no empty rows found - nothing to drop

    if skip_empty_columns:
        for header in headers[:]:
            if df[header].isnull().all():
                headers.remove(header)

    items = [{} for _ in range(df.shape[0])]

    for header in headers:
        for row in range(df.shape[0]):
            value = df[header][row]
            if type(value) is float:  # because pandas imports empty cells as float("nan") (even with dtype=str)
                value = ''

            if not skip_empty_fields or value.strip() != '':
                items[row][header] = value

    return items, headers, header_types


def db_get_username(cursor: MySQLCursor) -> str:
    """
    Returns the username of the first Koillection user if only one user exists.
    Otherwise, prints available users and exits.
    :param cursor: opened cursor to the koillection database
    :return: name of the Koillection user
    """
    cursor.execute("SELECT username FROM koi_user")
    usernames = [name[0] for name in cursor.fetchall()]

    if not usernames:
        print("There should be at least one user.")
        sys.exit(1)
    elif len(usernames) > 1:
        print("Argument --user is required when there are multiple Koillection users present. Available users:")
        for name in usernames:
            print(name)
        sys.exit(1)

    return usernames[0]


def db_get_collection_id(cursor: MySQLCursor, collection_name: str, owner_id: str) -> str:
    """
    Finds a collection with the provided name. If it doesn't exist, creates a new collection belonging to owner_id.
    Returns the collection id.
    :param cursor: opened cursor to the koillection database
    :param collection_name: name of the collection to find or create
    :param owner_id: id of the collection owner (applicable if creating a new collection)
    :return: collection id
    """
    cursor.execute("SELECT title FROM koi_collection")
    collection_names = [name[0] for name in cursor.fetchall()]
    if collection_name not in collection_names:  # create a new collection
        collection_id = insert_collection(cursor, owner_id, collection_name)
        insert_log(cursor, owner_id, collection_id, collection_name, "App\\Entity\\Collection")
    else:
        cursor.execute(f"SELECT id FROM koi_collection WHERE title='{collection_name}'")
        collection_id = cursor.fetchone()[0]

    return collection_id


def insert_display_configuration(cursor: MySQLCursor, owner_id: str) -> str:
    """
    Inserts a single display configuration entry into the koi_display_configuration table and returns its id
    :param cursor: opened cursor to the koillection database
    :param owner_id:
    :return: id of the created display configuration
    """
    display_configuration_data = {
        "id": generate_id(),
        "owner_id": owner_id,
        "display_mode": "list",  # available options: "list", "grid"
        "sorting_direction": "ASC",  # available options: "ASC", "DESC"
        "created_at": get_current_time(),
        "columns": "a:0:{}"
    }
    insert_statement = (
        "INSERT INTO "
        "koi_display_configuration (id, owner_id, display_mode, sorting_direction, created_at, columns) "
        "values (%(id)s, %(owner_id)s, %(display_mode)s, %(sorting_direction)s, %(created_at)s, %(columns)s)"
    )
    cursor.execute(insert_statement, display_configuration_data)

    return display_configuration_data["id"]


def insert_collection(cursor: MySQLCursor, owner_id: str, collection_name: str) -> str:
    """
    Inserts a single collection into the koi_collection table and returns its id
    :param cursor: opened cursor to the koillection database
    :param owner_id: id of the collection's owner
    :param collection_name: name of the collection
    :return: id of the created collection
    """

    children_display_configuration_id = insert_display_configuration(cursor, owner_id)
    items_display_configuration_id = insert_display_configuration(cursor, owner_id)

    collection_data = {
        "id": generate_id(),
        "owner_id": owner_id,
        "title": collection_name,
        "color": random.choice(COLORS),
        "seen_counter": 0,
        "visibility": DEFAULT_COLLECTION_VISIBILITY,
        "created_at": get_current_time(),
        "final_visibility": DEFAULT_COLLECTION_VISIBILITY,
        "items_default_template_id": None,
        "children_display_configuration_id": children_display_configuration_id,
        "items_display_configuration_id": items_display_configuration_id,
        "cached_values": """{"prices": [], "counters": {"items": 0, "children": 0}}"""
    }
    insert_statement = (
        "INSERT INTO "
        "koi_collection (id, owner_id, title, color, seen_counter, visibility, created_at,"
        "                final_visibility, items_default_template_id, children_display_configuration_id,"
        "                items_display_configuration_id, cached_values) "
        "values         (%(id)s, %(owner_id)s, %(title)s, %(color)s, %(seen_counter)s, %(visibility)s, %(created_at)s,"
        "                %(final_visibility)s, %(items_default_template_id)s, %(children_display_configuration_id)s,"
        "                %(items_display_configuration_id)s, %(cached_values)s)"
    )
    cursor.execute(insert_statement, collection_data)

    return collection_data["id"]


def insert_datum(cursor: MySQLCursor, owner_id: str, item_id: str, datum_name: str, datum_value: str, datum_type: str, datum_position: int, visibility: str) -> str:
    """
    Inserts a single datum into the koi_datum table and returns its id
    :param cursor: opened cursor to the koillection database
    :param owner_id: id of the datum's owner (user)
    :param item_id: id of the item this datum refers to
    :param datum_name: name of the datum
    :param datum_value: value of the datum
    :param datum_type: type of the datum (currently text or textarea, although Koillection allows more types)
    :param datum_position: position of the datum in the item (datums are displayed in ascending position order)
    :param visibility: visibility of the datum (public/private/internal)
    :return: id of the created datum
    """
    datum_data = {
        "id": generate_id(),
        "item_id": item_id,
        "owner_id": owner_id,
        "type": datum_type,
        "label": datum_name,
        "value": datum_value,
        "position": datum_position,
        "created_at": get_current_time(),
        "visibility": visibility
    }
    insert_statement = (
        "INSERT INTO "
        "koi_datum (id, item_id, owner_id, type, label,"
        "           value, position, created_at, visibility) "
        "values    (%(id)s, %(item_id)s, %(owner_id)s, %(type)s, %(label)s,"
        "           %(value)s, %(position)s, %(created_at)s, %(visibility)s)"
    )
    cursor.execute(insert_statement, datum_data)

    return datum_data["id"]


def insert_item(cursor: MySQLCursor, owner_id: str, collection_id: str, item_name: str, item_quantity: int) -> str:
    """
    Inserts a single item into the koi_item table and returns its id
    :param cursor: opened cursor to the koillection database
    :param owner_id: id of the item's owner
    :param collection_id: id of the item's collection
    :param item_name: name of the item
    :param item_quantity: quantity of the item
    :return: id of the created item
    """
    item_data = {
        "id": generate_id(),
        "collection_id": collection_id,
        "owner_id": owner_id,
        "name": item_name,
        "quantity": item_quantity,
        "seen_counter": 0,
        "visibility": "public",
        "created_at": get_current_time(),
        "parent_visibility": DEFAULT_COLLECTION_VISIBILITY,
        "final_visibility": DEFAULT_COLLECTION_VISIBILITY
    }
    insert_statement = (
        "INSERT INTO "
        "koi_item (id, collection_id, owner_id, name, quantity, seen_counter, visibility,"
        "          created_at, parent_visibility, final_visibility) "
        "values   (%(id)s, %(collection_id)s, %(owner_id)s, %(name)s, %(quantity)s, %(seen_counter)s, %(visibility)s,"
        "          %(created_at)s, %(parent_visibility)s, %(final_visibility)s)"
    )
    cursor.execute(insert_statement, item_data)

    return item_data["id"]


def insert_log(cursor: MySQLCursor, owner_id: str, object_id: str, object_name: str, object_class: str) -> str:
    """
    Inserts a single log entry into the koi_log table and returns its id
    :param cursor: opened cursor to the koillection database
    :param owner_id: id of the log entry owner (user)
    :param object_id: id of the object the log entry refers to
    :param object_name: name of the object the log entry refers to
    :param object_class: class of the object the log entry refers to
    :return: id of the created log entry
    """

    log_data = {
        "id": generate_id(),
        "owner_id": owner_id,
        "type": "create",
        "logged_at": get_current_time(),
        "object_id": object_id,
        "object_label": object_name,
        "object_class": object_class
    }
    insert_statement = (
        "INSERT INTO "
        "koi_log (id, owner_id, type, logged_at, object_id, object_label, object_class) "
        "values  (%(id)s, %(owner_id)s, %(type)s, %(logged_at)s, %(object_id)s, %(object_label)s, %(object_class)s)"
    )
    cursor.execute(insert_statement, log_data)

    return log_data["id"]


def parse_args() -> dict:
    """
    Parses the command line arguments
    :return: dictionary containing the arguments
    """
    example_usage = "Example usage:\n\t"\
                    "./import_data.py -f zestawy.xls -F ./../docker-compose-koillectionPG_dev.yml "\
                    "-S \"Karty Graficzne\" -c \"Karty Graficzne\" -p Uwagi \"Informacje dodatkowe\" -s L.p. wystawa? "\
                    "--skip_empty_columns\n\n\t"\
                    "- Will import data from the 'Karty Graficzne' sheet of 'zestawy.xls' file, using the "\
                    "'./../docker-compose-koillectionPG_dev.yml' compose file, into a collection named "\
                    "'Karty Graficzne', while marking fields 'Uwagi' and 'Informacje dodatkowe' as private, "\
                    "skipping importing fields 'L.p.' and 'wystawa?', and skipping all empty columns."
    parser = argparse.ArgumentParser(description="Import data from a file (Excel or csv) into Koillection database",
                                     epilog=example_usage,
                                     formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("-f", "--file", type=str, required=True, help="Path to the file containing data")
    parser.add_argument("-F", "--compose_file", type=str, required=True, help="Path to the docker compose file")

    parser.add_argument("-S", "--sheet", type=str,
                        help="Name of the Excel sheet to import data from. Only applicable when importing Excel files. "
                             "Will use the first sheet if omitted.")

    parser.add_argument("-n", "--name_column", type=str, default=DEFAULT_NAME_COLUMN,
                        help=f"Column containing item names. Default={DEFAULT_NAME_COLUMN}")
    parser.add_argument("-q", "--quantity_column", type=str,
                        help="Column containing item quantity. Quantity is set to 1 if not specified")
    parser.add_argument("-p", "--private_fields", type=str, nargs='+', default=[],
                        help="Column names that will be made private")
    parser.add_argument("-s", "--skip_fields", type=str, nargs='+', default=[],
                        help="Column names that will be skipped")
    parser.add_argument("-c", "--collection", type=str, required=True,
                        help="Name of the collection to import data into. "
                             "A new collection will be created if it does not exist.")
    parser.add_argument("-u", "--user", type=str,
                        help="Koillection user that will become the owner of the newly created collection "
                             "(not required when there is only one user)")

    parser.add_argument("--skip_empty_columns", action="store_true",
                        help="Removes all columns that don't have a value in any item")
    parser.add_argument("--skip_empty_fields", action="store_true",
                        help="Removes fields that don't have a value (works per-item)")

    parser.add_argument("--host", type=str, help="Database host ip")

    args = vars(parser.parse_args())

    args["private_fields"] = [field_name.lower() for field_name in args["private_fields"]]
    args["skip_fields"] = [field_name.lower() for field_name in args["skip_fields"]]

    return args


def load_environment_variables(compose_file: str) -> dict:
    """
    Loads environmental variables from the koillection service, from the provided compose file
    :param compose_file: path to the compose file
    :return: dictionary containing the environmental variables and their values
    """

    with open(compose_file, 'r', encoding='utf-8') as file:
        compose = yaml.safe_load(file)

    environment = {}
    for item in compose["services"]["koillection"]["environment"]:
        key, value = item.split('=')
        environment[key] = value

    return environment


def main() -> None:
    """The main importer function. Loads all the required data and imports items into the database"""

    args = parse_args()
    environment = load_environment_variables(args["compose_file"])

    cnx = mysql.connector.connect(
        user=environment["DB_USER"],
        password=environment["DB_PASSWORD"],
        host=args["host"] if args["host"] is not None else get_database_host(environment["DB_HOST"]),
        port=environment["DB_PORT"],
        database="koillection"
    )
    cursor = cnx.cursor()

    items, headers, header_types = load_data(args["file"], args["sheet"], args["skip_empty_columns"], args["skip_empty_fields"])

    username = args["user"] if args["user"] else db_get_username(cursor)

    cursor.execute(f"SELECT id FROM koi_user WHERE username='{username}'")
    owner_id = cursor.fetchone()[0]

    collection_id = db_get_collection_id(cursor, args["collection"], owner_id)

    # insert all items
    for item in items:
        item_name = item.get(args["name_column"], "").strip()
        item_name = DEFAULT_ITEM_NAME if not item_name else item_name
        item_quantity = 1 if not args["quantity_column"] else item.get(args["quantity_column"], "")
        item_quantity = 0 if item_quantity == "" else item_quantity
        item_id = insert_item(cursor, owner_id, collection_id, item_name, item_quantity)

        for index, field_name in enumerate(headers):
            if field_name != args["name_column"] and field_name != args["quantity_column"] and field_name.lower() not in args["skip_fields"] and item.get(field_name) is not None:
                insert_datum(
                    cursor=cursor,
                    owner_id=owner_id,
                    item_id=item_id,
                    datum_name=field_name,
                    datum_value=item[field_name],
                    datum_type=header_types[field_name],
                    datum_position=index + 1,
                    visibility="private" if field_name.lower() in args["private_fields"] else "public"
                )

        insert_log(cursor, owner_id, item_id, item_name, "App\\Entity\\Item")

    # update the collection's cached values to reflect the current items number
    cursor.execute(f"SELECT cached_values FROM koi_collection WHERE id='{collection_id}'")
    cached_values = ast.literal_eval(cursor.fetchone()[0])
    cached_values["counters"]["items"] += len(items)
    cursor.execute(f"UPDATE koi_collection SET cached_values=%s, updated_at=%s WHERE id='{collection_id}'",
                   (json.dumps(cached_values), get_current_time()))

    cnx.commit()
    cursor.close()
    cnx.close()


if __name__ == "__main__":
    main()
    sys.exit(0)
