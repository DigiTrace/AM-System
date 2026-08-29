"use strict";

// Extended search class
const ExtendedSearch = function(
    search_form,
    single_regex,
    multi_regex,
    keys,
    es_form = '#es_form',
) {
    const SEARCH_FORM = search_form;
    const SINGLE_VALUE_REGEX = single_regex;
    const MULTIPLE_VALUE_REGEX = multi_regex;
    const ES_FORM = es_form;

    // generate keys map
    const KEYS = new Map(keys);
    const REV_KEYS = new Map([...KEYS.entries()].map(e => [e[1], e[0]]));

    let state = [{}];

    const init = function(){
        // handler to activate operator selection
        $(`${ES_FORM} .es-input form-control`).on("change", function(e){
            // if no operator is selected, select first
            if($(`input[name='${this.name}.operator']:checked`).length === 0){
                const radio = $(`input[name="${this.name}.operator"]`).first();
                radio.prop('checked', true);
                radio.parent().addClass("active");
            }
        });

        // handler to trigger date range
        $(`input[name='date.operator']`).on("change", function(e){
            // show date end picker
            if (this.value == ">="){
                $(".es-date-end").show();
                $(".es-date-end-placeholder").hide();
            }
            else {
                $("#es-date-end").val("");
                $(".es-date-end").hide();
                $(".es-date-end-placeholder").show();
            }
        });

        // handler to submit search form
        $(ES_FORM).on("submit", function(e){
            e.preventDefault();
            $(SEARCH_FORM).trigger("submit");
        });

        // handler to update search input when form control changes
        $(ES_FORM).on("change", function(e){
            updateQuery();
        });

        // handler to update internal state and form controls
        $(SEARCH_FORM).on("input", function(e){
            updateForm();
        });

        // handler to add new search query segment
        $("#es_add_query").on('click', function(e){
            $(`${SEARCH_FORM} input`).val(' || ' + $(`${SEARCH_FORM} input`).val());
            updateForm();
        })

        // update State
        updateForm();
        console.debug('Init extended search');
    };
        
    // sets `state` variable based on extended search form
    // form -> state
    const parseForm = function(){
        // transform data into a map of entries
        const data = $(ES_FORM).serializeArray().reduce(function(map, item){
            const name = item.name.split('.')[0];
            const value = item.value;
            const isOperator = item.name.endsWith(".operator")                
            
            // get existing entry or create new empty
            const entry = map.get(name) ?? {
                name: name,
                key: KEYS.get(name),
                value: [],
                operator: '',
                negate: false,
            }

            // add operator
            if (isOperator){
                switch (value) {
                    // equal
                    default:
                    case "":
                        break;
                    // not equal
                    case "!":
                        entry.negate = true;
                        break;
                    // less then, greater then
                    case "<":
                    case ">":
                    // range
                    case ">=":
                    case "<=":
                        entry.operator = value;
                        break;
                    // true
                    case "*":
                        entry.value = ["t"];
                        break;
                    // false
                    case "-":
                        entry.value = ["f"];
                        break;
                }
            }
            // or add value
            else if(value) {
                // check if multiple selection
                const valuesToAdd = value.includes('|') ?
                    value.split('|').map((x) => x.trim()) :
                    [value];

                // escape whitespace if not already
                valuesToAdd.forEach(element => {
                    if((element.startsWith('"') && element.endsWith('"')) ||
                        (element.startsWith("'") && element.endsWith("'"))){
                        entry.value.push(element);
                    }
                    else {
                        entry.value.push(element.includes(' ') ? `"${element}"` :element);
                    }
                });
            }

            // update map
            map.set(name, entry);
            return map;
        }, new Map());

        state[0] = Array.from(data.values());
    }

    // sets `state` property based on search query input
    // query -> state
    const parseQuery = function(){
        const data = $(`${SEARCH_FORM} input`).val().split("||");
        
        // update state by parsing state
        state = data.map(function(segment){
            
            // process single
            const single = segment.matchAll(SINGLE_VALUE_REGEX).map(function(entry){
                const key = entry[1].replace("!", "");
                const raw_value = entry[2].replace(/["']/g, ""); 
                let operator = '';
                let value = raw_value;

                if (raw_value.match(/^[<>]=./)){
                    operator = raw_value.substring(0, 1);
                    value = raw_value.substring(2);
                }
                else if (raw_value.match(/^[<>]=./)){
                    operator = raw_value.substring(0, 0);
                    value = raw_value.substring(1);
                }

                return {
                    name: REV_KEYS.get(key) ?? '',
                    key: key,
                    value: [value],
                    operator: operator,
                    negate: entry[1].startsWith("!"),
                }
            });
            // process multiple
            const multiple = segment.matchAll(MULTIPLE_VALUE_REGEX).map(function(entry){
                const key = entry[1].replace("!", "");
                const values = entry[2].split("|").map(e => e.replace(/["']/g, ""));

                const operator = values.length && values[0].match(/^[<>]./) ?
                    values[0][0] : '';

                return {
                    name: REV_KEYS.get(key) ?? '',
                    key: key,
                    value: values,
                    operator: operator,
                    negate: entry[1].startsWith("!"),
                }
            });

            // merge
            return [...single, ...multiple];
        });
    }

    // apply `state` to form controlls to sync form with state
    // state -> form
    const applyStateToForm = function(){
        // reset form
        document.querySelector(ES_FORM).reset();
        $(".es-operator-btn").removeClass('active');

        // for now only support for one/first OR-segment
        state[0].forEach(function(entry) {
            if (entry.value){
                const value = entry.value.length == 1 ? entry.value[0] : `${entry.value.join(" | ")}`;
                const name = entry.name;
                const operator = entry.operator;
                
                // custom handler for endate (ed)
                if(name == "end_date"){
                    // show input
                    $(".es-date-end").show();
                    $(".es-date-end-placeholder").hide();
                    // set value
                    $("#es-date-end").val(value);
                    // overide date operator
                    $(`input[name='date.operator']`).prop('checked', false).parent().removeClass("active");
                    $("#es_date_range_operator").prop('checked', true).parent().addClass("active");
                }
                else if (name) {
                    // set values
                    if (entry.key == 'c' || entry.key == 's'){
                        $(`${ES_FORM} select[name='${name}']`).val(entry.value);
                    }
                    else if (value != 't' && value != 'f'){
                        $(`${ES_FORM} input[name='${name}']`).val(value);
                    }

                    // clear prev operator
                    $(`${ES_FORM} input[name='${name}.operator']`).prop('checked', false).parent().removeClass("active");

                    let opcode = '';
                    // set operator
                    if (entry.negate) {
                        opcode = '!';
                    } else if (operator === '<' || operator === '>') {
                        opcode = operator;
                    } else if (value === 't') {
                        opcode = '*'
                    } else if (value === 'f') {
                        opcode = '-'
                    }
                    $(`${ES_FORM} input[name='${name}.operator'][value="${opcode}"]`).prop('checked', true).parent().addClass("active");
                }
            }
        });

    }

    // apply `state` to text query
    // state -> query
    const applyStateToQuery = function(){
        const query = state.map(function(segment){
            return segment.filter(e => e.value.length).map(function(entry){
                const key = entry.key;
                const neg = entry.negate ? '!' : '';
                const value = entry.value.length == 1 ? entry.operator + entry.value[0] : `[${entry.value.join("|")}]`; 
                return `${neg}${key}:${value}`;
            }).join(" ");
        }).join(" || ");
        $(`${SEARCH_FORM} input`).val(query);
    }

    // called when query is changed through form controls
    // updates text in search input
    const updateQuery = function(){
        parseForm();
        applyStateToQuery();
    }

    // called when search text is changed
    // updates internal state and form controls
    const updateForm = function(){
        parseQuery();
        applyStateToForm();
    }

    
    return {
        init: init,
    }
};
