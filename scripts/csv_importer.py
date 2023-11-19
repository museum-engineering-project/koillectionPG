#!/usr/bin/env python3

import ast
import csv
import datetime
import json
import uuid

try:
    import mysql.connector
except ImportError:
    print("Module mysql.connector is required.")
    print("You can install it with: pip install mysql-connector-python")
    exit()

USER = 'root'
PASSWORD = 'password'
HOST = '172.18.0.3'
PORT = '3306'

current_time = datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')


def get_current_time():
    # return datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    return current_time  # let's assume everything happens at the same time


def generate_id():
    return str(uuid.uuid4())


def load_data(path):
    items = []
    with open(path, 'r', encoding="utf-8") as file:
        reader = csv.DictReader(file)
        for row in reader:
            items.append(row)
    return items


def insert_display_configuration(owner_id):
    display_configuration_data = {
        "id": generate_id(),
        "owner_id": owner_id,
        "label": None,
        "display_mode": "list",
        "sorting_property": None,
        "sorting_type": None,
        "sorting_direction": "ASC",
        "show_visibility": 1,
        "show_actions": 1,
        "show_number_of_children": 1,
        "show_number_of_items": 1,
        "created_at": get_current_time(),
        "updated_at": None,
        "columns": "a:0:{}"  # I have no idea what this does
    }
    insert_statement = (
        "INSERT INTO "
        "koi_display_configuration (id, owner_id, label, display_mode, sorting_property, sorting_type, sorting_direction, show_visibility, show_actions, show_number_of_children, show_number_of_items, created_at, updated_at, columns) "
        "values (%(id)s, %(owner_id)s, %(label)s, %(display_mode)s, %(sorting_property)s, %(sorting_type)s, %(sorting_direction)s, %(show_visibility)s, %(show_actions)s, %(show_number_of_children)s, %(show_number_of_items)s, %(created_at)s, %(updated_at)s, %(columns)s)")
    cursor.execute(insert_statement, display_configuration_data)

    return display_configuration_data["id"]


def insert_collection(owner_id, collection_name):
    children_display_configuration_id = insert_display_configuration(owner_id)
    items_display_configuration_id = insert_display_configuration(owner_id)

    collection_data = {
        "id": generate_id(),
        "parent_id": None,
        "owner_id": owner_id,
        "title": collection_name,
        "color": "000000",
        "image": None,
        "seen_counter": 0,
        "visibility": "private",  # can be changed to public or internal
        "created_at": get_current_time(),
        "updated_at": None,
        "parent_visibility": None,
        "final_visibility": "private",  # can be changed to public or internal
        "items_default_template_id": None,
        "children_display_configuration_id": children_display_configuration_id,
        "items_display_configuration_id": items_display_configuration_id,
        "cached_values": """{"prices": [], "counters": {"items": 0, "children": 0}}"""
    }
    insert_statement = (
        "INSERT INTO "
        "koi_collection (id, parent_id, owner_id, title, color, image, seen_counter, visibility, created_at, updated_at, parent_visibility, final_visibility, items_default_template_id, children_display_configuration_id, items_display_configuration_id, cached_values) "
        "values         (%(id)s, %(parent_id)s, %(owner_id)s, %(title)s, %(color)s, %(image)s, %(seen_counter)s, %(visibility)s, %(created_at)s, %(updated_at)s, %(parent_visibility)s, %(final_visibility)s, %(items_default_template_id)s, %(children_display_configuration_id)s, %(items_display_configuration_id)s, %(cached_values)s)")
    cursor.execute(insert_statement, collection_data)

    return collection_data["id"]


def insert_datum(owner_id, item_id, datum_name, datum_value, datum_position, visibility):
    datum_data = {
        "id": generate_id(),
        "item_id": item_id,
        "owner_id": owner_id,
        "type": "text",  # todo?
        "label": datum_name,
        "value": datum_value,
        "position": datum_position,
        "image": None,
        "image_small_thumbnail": None,
        "created_at": get_current_time(),
        "updated_at": None,
        "collection_id": None,
        "file": None,
        "original_filename": None,
        "image_large_thumbnail": None,
        "choice_list_id": None,
        "visibility": visibility
    }
    insert_statement = (
        "INSERT INTO "
        "koi_datum (id, item_id, owner_id, type, label, value, position, image, image_small_thumbnail, created_at, updated_at, collection_id, file, original_filename, image_large_thumbnail, choice_list_id, visibility) "
        "values    (%(id)s, %(item_id)s, %(owner_id)s, %(type)s, %(label)s, %(value)s, %(position)s, %(image)s, %(image_small_thumbnail)s, %(created_at)s, %(updated_at)s, %(collection_id)s, %(file)s, %(original_filename)s, %(image_large_thumbnail)s, %(choice_list_id)s, %(visibility)s)")
    cursor.execute(insert_statement, datum_data)

    return datum_data["id"]


def insert_item(owner_id, collection_id, item_name):
    item_data = {
        "id": generate_id(),
        "collection_id": collection_id,
        "owner_id": owner_id,
        "name": item_name,
        "quantity": 1,  # todo?
        "image": None,
        "image_small_thumbnail": None,
        "seen_counter": 0,
        "visibility": "public",
        "created_at": get_current_time(),
        "updated_at": None,
        "image_large_thumbnail": None,
        "parent_visibility": "public",
        "final_visibility": "public"
    }
    insert_statement = (
        "INSERT INTO "
        "koi_item (id, collection_id, owner_id, name, quantity, image, image_small_thumbnail, seen_counter, visibility, created_at, updated_at, image_large_thumbnail, parent_visibility, final_visibility) "
        "values   (%(id)s, %(collection_id)s, %(owner_id)s, %(name)s, %(quantity)s, %(image)s, %(image_small_thumbnail)s, %(seen_counter)s, %(visibility)s, %(created_at)s, %(updated_at)s, %(image_large_thumbnail)s, %(parent_visibility)s, %(final_visibility)s)")
    cursor.execute(insert_statement, item_data)

    return item_data["id"]


def insert_log(owner_id, object_id, object_name, object_class):
    log_data = {
        "id": generate_id(),
        "owner_id": owner_id,
        "type": "create",
        "logged_at": get_current_time(),
        "object_id": object_id,
        "object_label": object_name,
        "object_class": object_class,
        "object_deleted": 0
    }
    insert_statement = (
        "INSERT INTO "
        "koi_log (id, owner_id, type, logged_at, object_id, object_label, object_class, object_deleted) "
        "values  (%(id)s, %(owner_id)s, %(type)s, %(logged_at)s, %(object_id)s, %(object_label)s, %(object_class)s, %(object_deleted)s)")
    cursor.execute(insert_statement, log_data)

    return log_data["id"]


def main():
    file_path = input("CSV file path: ")
    items = load_data(file_path)

    headers = [item for item in items[0].keys() if item.strip() != '']
    name_field = headers[0]
    print(f"\nHeaders: {list(headers)}")
    print(f"Default item name will use the value in column: {name_field}.")
    if input("Use a different item name? (y/n) ") == 'y':
        name_field = None
        while name_field not in headers:
            name_field = input("Item name: ")

    print("By default all fields will be public.")
    print("Enter names of fields that should be made private, separated by commas (only commas, not comma+space!).")
    private_fields = input(">>").split(',')

    # print("\nCollections with matching headers:")
    # todo fetch list of all templates, compare headers with fields of each template, print template if they match

    choice = None
    while choice not in ('1', '2'):
        print()
        print("1. Create a new collection")
        print("2. Add to existing collection")
        choice = input(">>")

    create_new_collection = False
    if choice == '1':
        create_new_collection = True

    if not create_new_collection:
        cursor.execute("SELECT title FROM koi_collection")
        collection_names = [name[0] for name in cursor.fetchall()]
        print("\nCollections:")
        for name in collection_names:
            print(name)

    collection_name = input("Collection name: ")

    cursor.execute("SELECT username FROM koi_user")
    usernames = [name[0] for name in cursor.fetchall()]
    if len(usernames) == 1:
        username = usernames[0]
    else:
        print("\nUsers:")
        for name in usernames:
            print(name)

        username = None
        while username not in usernames:
            username = input("Collection owner name: ")

    cursor.execute(f"SELECT id FROM koi_user WHERE username='{username}'")
    owner_id = cursor.fetchone()[0]

    if create_new_collection:
        collection_id = insert_collection(owner_id, collection_name)
        insert_log(owner_id, collection_id, collection_name, "App\\Entity\\Collection")
    else:
        while collection_name not in collection_names:
            collection_name = input("Collection name: ")
            print(collection_names, type(collection_names))

        cursor.execute(f"SELECT id FROM koi_collection WHERE title='{collection_name}'")
        collection_id = cursor.fetchone()[0]

    for item in items:
        item_id = insert_item(owner_id, collection_id, item[name_field])

        for index, attribute_name in enumerate(headers):
            if attribute_name != name_field:
                insert_datum(owner_id, item_id, attribute_name, item[attribute_name], index + 1,
                             "private" if attribute_name in private_fields else "public")

        insert_log(owner_id, item_id, item[name_field], "App\\Entity\\Item")

    cursor.execute(f"SELECT cached_values FROM koi_collection WHERE id='{collection_id}'")
    cached_values = ast.literal_eval(cursor.fetchone()[0])
    cached_values["counters"]["items"] += len(items)
    cursor.execute(f"UPDATE koi_collection SET cached_values=%s, updated_at=%s WHERE id='{collection_id}'",
                   (json.dumps(cached_values), get_current_time()))


if __name__ == "__main__":
    cnx = mysql.connector.connect(user=USER, password=PASSWORD,
                                  host=HOST, port=PORT,
                                  database='koillection')
    cursor = cnx.cursor()

    main()

    cnx.commit()
    cursor.close()
    cnx.close()
