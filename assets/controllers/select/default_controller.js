import { Controller } from '@hotwired/stimulus';
import Translator from "bazinga-translator";
import { TsSelect2 } from "../../node_modules/ts-select2/dist/core";

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    select2;

    connect() {
        let self = this;

        this.select2 = new TsSelect2(this.element, {
            templateSelection: function (element) {
                if (!element.text) {
                    return self.htmlToElement('<span class="select-placeholder">' + Translator.trans('select2.none') + '</span>');
                }

                return self.htmlToElement('<div><span>' + self.transMlang(element.text) + '</span></div>');
            },
            templateResult: function (element) {
                if (!element.text && !element.children) {
                    return self.htmlToElement('<div><span class="select-placeholder">' + Translator.trans('select2.none') + '</span></div>');
                }

                return self.htmlToElement('<div><span>'+ self.transMlang(element.text) + '</span></div>');
            },
            language: {
                noResults: function () {
                    return Translator.trans('select2.no_results');
                },
                searching: function () {
                    return Translator.trans('select2.searching');
                }
            },
            sorter: function (data) {
                if (data && data.length>1 && data[0].rank) {
                    data.sort(function(a,b) {return (a.rank > b.rank) ? -1 : ((b.rank > a.rank) ? 1 : 0);} );
                }

                return data;
            },
            matcher: function(params, data) {
                if (typeof params.term === 'undefined' || params.term.trim() === '') {
                    return data;
                }

                if (typeof data.text === 'undefined') {
                    return null;
                }

                let idx = data.text.toLowerCase().indexOf(params.term.toLowerCase());
                if (idx > -1) {
                    let rank = {
                        'rank': (params.term.length / data.text.length) + (data.text.length-params.term.length-idx)/(3*data.text.length)
                    };

                    return {...rank, ...data};
                }

                return null;
            }
        })
    }

    htmlToElement(html) {
        let template = document.createElement('template');
        html = html.trim();
        template.innerHTML = html;
        return template.content.firstChild;
    }

    update({ detail: { value } }) {
        this.select2.val(value);
    }

    transMlang(text) {
        if (text === null) {
            return "";
        }

        var openingTag = "{[ \t]*mlang[ \t]*" + Translator.locale + "[ \t]*}";
        var closingTag = "{[ \t]*mlang[ \t]*}";

        var pattern = new RegExp(openingTag + ".*?" + closingTag, 'g');
        var matches = [...text.matchAll(pattern)];

        // if no tags were matched, try to match tags with default attribute
        if (matches.length === 0) {
            openingTag = "{[ \t]*mlang[ \t]*.*?[ \t]*default[ \t]*}";
            pattern = new RegExp(openingTag + ".*?" + closingTag, 'g');
            matches = [...text.matchAll(pattern)];
        }

        // remove mlang tags of matched locale while keeping their content
        for (let match of matches) {
            let originalMatch = match[0];
    
            match[0] = match[0].replace(new RegExp(openingTag), "");
            match[0] = match[0].replace(new RegExp(closingTag), "");
    
            text = text.replace(originalMatch, match[0]);
        }

        // remove all remaining (unmatched) mlang tags and their content
        text = text.replace(new RegExp("{[ \t]*mlang[ \t]*.*?[ \t]*}" + ".*?" + closingTag, 'g'), "");

        return text;
    }
}