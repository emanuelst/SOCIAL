#!/usr/bin/env python

# dot_to_formly_json.py
# http://stackoverflow.com/questions/40262441/how-to-transform-a-dot-graph-to-json-graph

# Packages needed:
# python-networkx python-pydot
# (was needed: python-pygraphviz to try to read from string, we use networkx and read from file currently)
#
# Syntax:
# python dot_to_formly_json.py

# Usage:
# python dot_to_formly_json.py `environment` `type` `filename`
# `environment` can be 'dev' or 'prod'
# `type` can be 'calculator' or 'questionnaire'
# `filename` will then be used for the url (e.g., https://social.cosy.univie.ac.at/filename) as parsed from backend

import sys
import json
import networkx as nx

# https://tecadmin.net/python-command-line-arguments/
environment = str(sys.argv[1])
type = str(sys.argv[2])
filename = str(sys.argv[3])


def get_elements_without_key(key):
    """get elements without a key
    https://stackoverflow.com/questions/40447612/filter-a-list-of-dictionaries-based-on-key-not-key-value-in-python#
    """
    return [d for d in graph_dict_nodes_formly if key not in d]


def add_hide_expr(node, concat_string, hide_expr):
    """add hide expression to node"""
    if 'hideExpression' in node:
        # empty string is false, don't add leading concat_string
        if not node['hideExpression']:
            node['hideExpression'] += hide_expr
        else:
            node['hideExpression'] += concat_string + hide_expr
    else:
        node.update({'hideExpression': hide_expr})


filename_dot_string = "models/" + filename + "_dot_string.txt"

f = open(filename_dot_string, "a+")
f.seek(0)
dag_string_from_dag_input_file = f.read()

dag_string = "digraph " + dag_string_from_dag_input_file

# write to temporary dot file to read (reading from file is easier than reading from string) & overwrite
temp_dot_file = open("models/temp_graph_dot.dot", "w")

temp_dot_file.write(dag_string)
temp_dot_file.close()

# read graph from file
dot_graph = nx.nx_pydot.read_dot("models/temp_graph_dot.dot")

# read from string
# g = nx_agraph.from_agraph(pygraphviz.AGraph(string=dag_string))
# print(json.dumps(json_graph.node_link_data(dot_graph), ensure_ascii=False).encode('utf8'))

# write graph to json
nxgraph = nx.readwrite.json_graph.node_link_data(dot_graph)

filename_graph_json = "models/" + filename + '_graph_json.json'

with open(filename_graph_json, 'w', encoding='utf8') as json_file:
    json.dump(nxgraph, json_file, ensure_ascii=False, indent=4)

# https://stackoverflow.com/questions/644178/how-do-i-re-map-python-dict-keys
# name_map needs to contain all keys for our elements, remap dictionary to ngx-formly style ('id' to 'key')
name_map = {'id': 'key',  # remapping
            'pos': 'pos',

            'question': 'question',
            'questionlabel': 'questionlabel',

            'input': 'input',
            'inputlabel': 'inputlabel',
            'inputsort': 'inputsort',
            'inputdefaultvalue': 'defaultValue',

            'answer': 'answer',
            'answerlabel': 'answerlabel',
            'answervalue': 'answervalue',
            'answersort': 'answersort',

            'decision': 'decision',
            'decisioncalculation': 'decisioncalculation',

            'result': 'result',
            'resultlabel': 'resultlabel',
            'resultcalculation': 'resultcalculation',

            'addlinfo': 'addlinfo'
            }

graph_dict_nodes_formly = [dict(zip(map(lambda x: name_map[x], r.keys()), r.values())) for r in nxgraph["nodes"]]

# for every node
for row in graph_dict_nodes_formly:
    if 'input' in row:
        row.update({'type': 'input'})
        row.update({'templateOptions': {'type': 'number'}})  # add input type number
    elif 'decision' in row:
        row.update({'type': 'radio'})
    else:
        row.update({'type': 'radio'})
    row.update({'templateOptions': {'label': row['key'], 'translate': "true"}})  # add label and translate true
    if 'addlinfo' in row:
        cur_string = row["addlinfo"]
        if cur_string.startswith('"') and cur_string.endswith('"'):  # remove quotes
            cur_string = cur_string[1:-1]
        row.update({'templateOptions': {'label': row['key'], 'translate': "true",
                                        "addlinfo": cur_string}})  # add label and translate true
    if 'input' in row:
        if type == "calculator" and row[
            'key'] != 'PersBeh':  # note, hardcoded; if we're in calculator mode and not on PersBeh, add euro signs
            row.update({'templateOptions': {'type': 'number', 'min': 0, 'label': row['key'], 'translate': "true",
                                            "placeholder": 0,
                                            "addonLeft": {"class": "fa fa-euro"}}})  # add label w/ type ;)
        if row['key'] == 'PersBeh':  # note, hardcoded
            row.update({'templateOptions': {'type': 'number', 'min': 0, 'label': row['key'], 'translate': "true",
                                            "placeholder": 0}})  # add label w/ type ;)
    if 'defaultValue' in row:  # add defaultValue and set to disabled if set, otherwise default to 0
        cur_string = row["defaultValue"]
        if cur_string.startswith('"') and cur_string.endswith('"'):  # remove quotes
            cur_string = cur_string[1:-1]
        row.update({'defaultValue': cur_string})
        row.update({'templateOptions': {'disabled': 'true', 'label': row['key'],
                                        'translate': "true"}})  # add disabled and add label and translate again to fix templateOptions
    # if 'defaultValue' not in row: # add defaultValue and set to disabled if set, otherwise default to 0
    # row.update({'defaultValue': 0})
    wrapperArray = ["form-field", "addons", "custompanel"]
    row.update({"wrappers": ["form-field", "addons", "custompanel"]})
    row.pop('pos', None)  # remove pos
    row.pop('addlinfo', None)  # remove addlinfo from main node (already added to templateOptions)

# strip special chars in graph_dict_nodes_formly keys (attention, keys might not be unique
# ("hello 1" vs "hello1" or "x<5" vs "x>5") --> enforce in GUI
for row in graph_dict_nodes_formly:
    # https://stackoverflow.com/questions/5843518/remove-all-special-characters-punctuation-and-spaces-from-string
    row['key'] = ''.join(e for e in row['key'] if e.isalnum())

inputFieldHideExpression = {}

# for every edge
# note, special chars are not stripped in links
# note, enforce no special characters (e.g. spaces in IDs)
for row in nxgraph["links"]:
    # strip special chars
    row["target"] = ''.join(e for e in row["target"] if e.isalnum())
    row["source"] = ''.join(e for e in row["source"] if e.isalnum())

    # https://stackoverflow.com/questions/4391697/find-the-index-of-a-dict-within-a-list-by-matching-the-dicts-value
    # set up our indices and nodes
    target_idx = next((idx for (idx, d) in enumerate(graph_dict_nodes_formly) if d["key"] == row["target"]), None)
    source_idx = next((idx for (idx, d) in enumerate(graph_dict_nodes_formly) if d["key"] == row["source"]), None)
    target = graph_dict_nodes_formly[target_idx]
    source = graph_dict_nodes_formly[source_idx]
    source_source_idx = next((idx for (idx, d) in enumerate(nxgraph["links"]) if d["target"] == source["key"]), None)
    source_key = source["key"]
    target_key = target["key"]

    # start template options
    current_answer_label = target_key  # default label
    if 'answerlabel' in target:
        cur_string = target['answerlabel']
        if cur_string.startswith('"') and cur_string.endswith('"'):  # remove quotes
            cur_string = cur_string[1:-1]
        current_answer_label = cur_string

    # does not work due to some translation code... use i18n for now
    # if 'inputlabel' in target:
    #    print("helo")
    #    cur_string = target['inputlabel']
    #    if cur_string.startswith('"') and cur_string.endswith('"'):  # remove quotes
    #        cur_string = cur_string[1:-1]
    #    current_answer_label = cur_string
    #    print(current_answer_label)

    # https://stackoverflow.com/questions/17370984/appending-an-id-to-a-list-if-not-already-present/55254150#55254150
    # add template options containing the answers to questions

    cur_answer_sort = ''  # default answer sort is empty string
    cur_answer_value = target_key  # default answer value is target key
    if 'answersort' in target:
        cur_answer_sort = target["answersort"]
    if 'answervalue' in target:
        cur_string = target["answervalue"]
        if cur_string.startswith('"') and cur_string.endswith('"'):  # remove quotes
            cur_string = cur_string[1:-1]
        cur_answer_value = cur_string

    template_options = source["templateOptions"].setdefault('options', [])
    template_options_string = {'label': current_answer_label, 'value': target_key,
                               'sort': cur_answer_sort}  # add label, value, sort
    if 'answervalue' in target:
        template_options_string = {'label': current_answer_label, 'value': cur_answer_value,
                                   'sort': cur_answer_sort}  # add label, value, sort

    if template_options_string not in template_options:
        template_options.append(template_options_string)
    # end template options
    # reshuffle options array according to sort val
    # https://stackoverflow.com/questions/72899/how-do-i-sort-a-list-of-dictionaries-by-a-value-of-the-dictionary)
    source["templateOptions"]["options"] = sorted(source["templateOptions"]["options"], key=lambda k: k['sort'])
    # print(source["templateOptions"]["options"])

    # https://formly.dev/guide/expression-properties
    # https://github.com/ngx-formly/ngx-formly/issues/2125#event-3096314415
    # build hideExpression for questions (i.e., hide questions when the corresponding answer was not given)

    # start hide expressions
    # add default hideExpression (empty string)
    targetHideExpression = ''

    # if index is None, we are on the first question (i.e., there is no source)
    if source_source_idx is not None:
        source_source = nxgraph["links"][source_source_idx]["source"]
        # first step, build default hideExpression for questions && radio buttons
        if 'question' or 'input' or 'result' in target:
            if 'decision' not in 'source':
                targetHideExpression = 'model.' + source_source + ' !== ' + "'" + source_key + "'"
            if 'decision' in source:
                targetHideExpression = 'model.' + source_key + ' !== ' + "'" + target_key + "'"

    if 'decision' in target and 'decisioncalculation' in target:
        # strip '\' and '"' (chars escaped) from calculation string
        decisioncalculation_clean = target['decisioncalculation'].strip("\\\"")
        # print(source)
        if 'input' in source:
            targetHideExpression = '!model.' + source_key + ' || ' + '!(model.' + decisioncalculation_clean + ')'
        elif 'answer' in source:
            targetHideExpression = 'model.' + source_source + ' !== ' + "'" + source_key + "'" + ' || ' + '!(model.' + decisioncalculation_clean + ')'

    # special case if target is question and source is 'decision'
    if 'question' in target and 'decision' in source:
        targetHideExpression = 'model.' + source_key + ' !== ' + "'" + target_key + "'"

    # special case if target is input and source is 'decision'
    if 'decision' in source and 'input' in target:
        targetHideExpression = 'model.' + source_key + ' !== ' + "'" + target_key + "'"

    # special case for "einkommen"
    if type == 'calculator':
        if 'decision' in source and 'decisioncalculation' in source and 'input' in target:
            # strip chars
            targetHideExpression = source['decisioncalculation'].strip("\\\"")
            add_hide_expr(target, " && ", targetHideExpression)

    # add hideExpression to targets and sources first
    # if 'result' not in target:
    if type == "questionnaire":
        # if 'result' not in target:
        add_hide_expr(target, " && ", targetHideExpression)

    # add hiding of "input" fields when one of the decision nodes is selected
    # build "input" field hideExpression string to add last
    # note: "number inputs need decision nodes to follow afterwards"
    # note: decision nodes for results...
    if 'decision' in target and 'input' in source:
        # if empty:
        if inputFieldHideExpression.get(source_key) is None:
            inputFieldHideExpression[source_key] = 'model.' + target_key
        else:
            inputFieldHideExpression[source_key] += " || " + 'model.' + target_key

# add hideExpression to "result" nodes to make form easier to read for experts. "results" are only defined in the model
# and shown in the sidebar, but not in the form
if type == "calculator":
    for row in graph_dict_nodes_formly:
        if 'result' in row or 'decision' in row:
            hideexpr = "true"
            add_hide_expr(row, "", hideexpr)

# add own hideExpression to make more quizzy, we need "||" for those
# only when in questionnaire mode
print(type)

if type == "questionnaire":
    print('we are questionnaire')
    print(inputFieldHideExpression)
    print(row['key'])
    for row in graph_dict_nodes_formly:
        if 'input' not in row:  # don't hide inputs on input
            ownHideExpression = 'model.' + row['key']
            add_hide_expr(row, " || ", ownHideExpression)
        else:  # add prepared statement for our inputs
            if inputFieldHideExpression.get(row['key']) is not None:
                add_hide_expr(row, " || ", inputFieldHideExpression[row['key']])
            # inputFieldHideExpression = "" #reset hide expression after adding

        # end hide expressions

# sort nodes according to answersort first, then sort again topo sort
# print(list(nx.topological_sort(dot_graph)))

# sort nodes in topological order & strip special chars in topo sort
# https://stackoverflow.com/questions/4081217/how-to-modify-list-entries-during-for-loop
nodes_in_topological_order = list(nx.topological_sort(dot_graph))
for i, s in enumerate(nodes_in_topological_order):
    nodes_in_topological_order[i] = ''.join(e for e in s if e.isalnum())

# https://stackoverflow.com/questions/25624106/sort-list-of-dictionaries-by-another-list
graph_dict_nodes_formly = sorted(graph_dict_nodes_formly, key=lambda x: nodes_in_topological_order.index(x['key']))

# elements with key 'answer' are 'answers', we only need the questions from our graph_dict_nodes_formly list
# we have added the answers as templateOptions already
only_questions = get_elements_without_key('answer')

# add key if we're questionnaire or calculator at beginning of list here
# use 'type' string, can be either questionnaire or calculator
form_type_dictionary = {"myFormType": type}
only_questions.insert(0, form_type_dictionary)
print(only_questions)

# write file to parent parent... src/assets/json-powered...

filename_for_angular = filename + '.json'

# default is dev
angularfilepath = '../../src/assets/json-powered/' + filename_for_angular

if environment == "dev":
    angularfilepath = '../../src/assets/json-powered/' + filename_for_angular
elif environment == "prod":
    angularfilepath = '/var/www/html/social.cosy.univie.ac.at/public_html/assets/json-powered/' + filename_for_angular

with open(angularfilepath, 'w', encoding='utf8') as json_file:
    json.dump(only_questions, json_file, ensure_ascii=False, indent=4)

print("success from python")
