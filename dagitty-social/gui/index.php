<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
    <!--
        DAGitty - a graphical tool for analyzing causal diagrams
        Copyright (C) 2010-2020 Johannes Textor, Benito van der Zander

        This program is free software; you can redistribute it and/or
        modify it under the terms of the GNU General Public License
        as published by the Free Software Foundation; either version 2
        of the License, or (at your option) any later version.

        This program is distributed in the hope that it will be useful,
        but WITHOUT ANY WARRANTY; without even the implied warranty of
        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
        GNU General Public License for more details.

        You should have received a copy of the GNU General Public License
        along with this program; if not, write to the Free Software
        Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
    -->

    <title>DAGitty v3.0</title>
    <link rel="stylesheet" type="text/css" href="dags.css" />
    <link rel="stylesheet" type="text/css" href="dags-social.css" />

    <script type="text/javascript" src="js/dagitty.js"></script>
    <script type="text/javascript" src="js/example-dags.js"></script>

    <script type="text/javascript" src="js/styles/semlike.js"></script>
    <script type="text/javascript" src="js/styles/original.js"></script>

    <script type="text/javascript" src="js/main.js"></script>
    <script type="text/javascript" src="js/base64.js"></script>

    <script type="text/javascript" src="js/custom-social.js"></script>

    <script type="text/javascript" src="https://www.google.com/recaptcha/api.js?render=explicit"
            async defer></script>

    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <script type="text/javascript">
        var redraw;
        var renderer;
        var layouter;
        var height = 300;
        var width = 600;
        var mouse_on_canvas;
        var resize_timeout;
        var mouse_x = 0;
        var mouse_y = 0;

        var Model = {
            dag : null,
            dag_active_bias_graph : null,
            dag_ancestor_moral_graph : null,
            dag_ancestor_pair_graph : null,
            dag_model_text_data : null
        };

        var DAGittyControl;

        function initialize(){
            DAGitty.setup({autofocus:false}); //set to false so our textinputs don't lose focus on mouseleave/mouseenter
            DAGittyControl = DAGitty.controllers[0];

            GUI.menus = ["model","examples","models","modelsdelete","howto"]
            _.each(GUI.menus,function(t){
                document.getElementById("menu_"+t+"_title").addEventListener( 'click',
                    function(){menuToggle(t)} );
            });

            DAGittyControl.observe( 'graphchange',
                function(g){
                    Model.dag = g;
                    document.getElementById("adj_matrix").value = g.toString();
                    // if previously highlighted variable no longer exists, fold up this menu
                    if( !g.getVertex(document.getElementById("variable_id").value) ){
                        document.getElementById("variable_id").value = ''
                    }
                    displayImplicationInfo( false )
                    causalEffectEstimates()
                    displayGeneralInfo()
                    GUI.refresh_variable_status()
                }
            );

            DAGittyControl.observe( 'graphlayoutchange',
                function(g){
                    document.getElementById("adj_matrix").value = g.toString();
                }
            );

            DAGittyControl.observe( 'vertex_marked',
                function( v ){
                    if( v ){
                        displayShow( 'variable' )
                        document.getElementById("variable_id").value = v.id
                        document.getElementById("variable_question_label").value = v.questionlabel
                        document.getElementById("variable_input_label").value = v.inputlabel
                        document.getElementById("variable_input_defaultvalue").value = v.inputdefaultvalue
                        document.getElementById("variable_input_sort").value = v.inputsort
                        document.getElementById("variable_answer_value").value = v.answervalue
                        document.getElementById("variable_answer_label").value = v.answerlabel
                        document.getElementById("variable_decision_calculation").value = v.decisioncalculation
                        document.getElementById("variable_result_label").value = v.resultlabel
                        document.getElementById("variable_result_calculation").value = v.resultcalculation
                        document.getElementById("variable_addl_info").value = v.addlinfo
                        document.getElementById("variable_answer_sort").value = v.answersort


                        GUI.refresh_variable_status()
                    } else {
                        if( !Model.dag.getVertex(document.getElementById("variable_id").value) ){
                            displayHide( 'variable' )
                        }
                    }
                }
            );

            var getid = (function(){
                var regexS = "[\\?&]id=([^&#]*)";
                var regex = new RegExp(regexS);
                var results = regex.exec(window.location.search);
                if(results == null)
                    return "";
                else
                    return decodeURIComponent(results[1].replace(/\+/g, " "));
            })();

            if( getid ){
                // load user-supplied model
                loadOnline( "dagitty.net/m"+getid );
            } else {
                DAGittyControl.graphChanged();
            }

            document.onkeydown = function(e){
                if( e.keyCode == 27 ){
                    DAGittyControl.getView().getImplementation().stopMousemove()
                    DAGittyControl.getView().closeDialog()
                }
            }
        }
        window.onload = initialize;
    </script>

</head>
<body>

<main>

    <ul id="menu">
        <li><a href="javascript:void(0)" id="menu_model_title">Model</a>
            <ul id="menu_model" style="z-Index:15">
                <li><a href="#" onclick="newModel();displayHide('menu_model')">New model</a></li>
                <!-- <li><a href="#" onclick="loadOnlineForm();displayHide('menu_model')">Load from dagitty.net</a></li> -->
                <script type="text/javascript">
                    if( document.location.protocol == "http:" || document.location.protocol == "https:" ){
                        // document.write('<li><a href="#" onclick="saveOnlineForm();displayHide(\'menu_model\')">Publish on dagitty.net</a></li>');
                        // document.write('<li><a href="#" onclick="updateOnlineForm();displayHide(\'menu_model\')">Update on dagitty.net</a></li>');
                        // document.write('<li><a href="#" onclick="deleteOnlineForm();displayHide(\'menu_model\')">Delete on dagitty.net</a></li>');
                    }

                    if( supportsSVG() ){
                        document.write( '<li><a href="#" onclick="exportPDF();displayHide(\'menu_model\')">Export as PDF</a></li>' );
                        document.write( '<li><a href="#" onclick="exportPNG();displayHide(\'menu_model\')">Export as PNG</a></li>' );
                        document.write( '<li><a href="#" onclick="exportJPEG();displayHide(\'menu_model\')">Export as JPEG</a></li>' );
                        document.write( '<li><a href="#" onclick="exportSVG();displayHide(\'menu_model\')">Export as SVG</a></li>' );
                    }
                </script>
                <li><a href="#" onclick="exportTikzCode();displayHide('menu_model')">Export LaTeX code</a></li>
            </ul>
        </li>
        <li><a href="javascript:void(0)" id="menu_examples_title">Examples</a>
            <ul id="menu_examples" style="z-Index:14">
                <script type="text/javascript">
                    for( var i = 0 ; i < examples.length ; i++ ){
                        document.write( "<li><a href=\"#\" onclick=\"loadExample("+i+");displayHide('menu_examples')\">"+examples[i].l+"</a></li>" );
                    }
                </script>
            </ul>
        </li>
        <li><a href="javascript:void(0)" id="menu_models_title">Load</a>
            <ul id="menu_models" style="z-Index:14">
                <?php

                // https://stackoverflow.com/a/34629093/841052
                function hasSuffix($var)
                {
                    // returns whether the input ends with ABC
                    return preg_match('/_dot_string.txt$/', $var);
                }

                // https://stackoverflow.com/a/15774702/841052
                $path = 'models/';
                $files = scandir($path);
                $files = array_diff(scandir($path), array('.', '..'));

                // https://www.php.net/manual/en/function.array-filter.php
                $files = array_filter($files, "hasSuffix");

                // echo print_r($files);

                ?>

                <!-- https://stackoverflow.com/a/47990744/841052 -->
                <script type="text/javascript">
                <?php $out = array(); $outcontent = array();
                foreach ($files as $filename) {
                    $p = pathinfo($filename);
                    $content = file_get_contents('models/' . $filename );
                    $out[] = $p['filename'];
                    $outcontent[] = $content;
                }
                // build javascript variable
                echo 'var files = ' . json_encode($out) . ';';
                echo 'var filescontent = ' .json_encode($outcontent) . ';'; ?>
                </script>

                <script type="text/javascript">
                    for (let i = 0; i < files.length; i++) {
                        document.write( "<li><a href=\"#\" onclick=\"document.getElementById('adj_matrix').value = filescontent["+i+"]; loadDAGFromTextData(); displayHide('menu_models')\">"+files[i]+"</a></li>" );
                    }
                </script>
            </ul>
        </li>
        <li><a href="javascript:void(0)" id="menu_modelsdelete_title">Delete</a>
            <ul id="menu_modelsdelete" style="z-Index:14">
                <script type="text/javascript">
                    function httpGetAsync(theUrl)
                    {
                        var xmlHttp = new XMLHttpRequest();
                        xmlHttp.onreadystatechange = function() {
                            //if (xmlHttp.readyState == 4 && xmlHttp.status == 200)
                                // callback(xmlHttp.responseText);
                        }
                        xmlHttp.open("GET", theUrl, true); // true for asynchronous
                        xmlHttp.send(null);
                    }

                    // https://stackoverflow.com/questions/15650131/creating-an-element-that-can-remove-it-self
                    for (let i = 0; i < files.length; i++) {
                        var url = encodeURI('deleteModel.php?model=' + files[i] + '.txt')
                        document.write( "<li><a href=\"#\" onclick=\"httpGetAsync('" + url + "', null); this.parentNode.removeChild(this); \" >"+files[i]+"</a></li>" );
                    }
                </script>
            </ul>
        </li>
        <li><a href="javascript:void(0)" id="menu_howto_title">How to ...</a>
            <ul id="menu_howto" style="z-Index:13">
                <li><a href="#" onclick="how('To add a new variable, click/tap on the canvas.')">Add a variable</a></li>
                <li><a href="#" onclick="how('To rename a variable, click/tap it and click/tap &quot;rename&quot; in the &quot;variable&quot; tab, or move the mouse over it and press the &quot;r&quot; key on your keyboard.')">Rename a variable</a></li>
                <li><a href="#" onclick="how('To add a new connection, click/tap on the start variable and then on the end variable.')">Add a connection</a></li>
                <li><a href="#" onclick="how('To delete a variable, click/tap it and then clik/tap &quot;delete&quot; in the &quot;variable&quot; tab, or move the mouse over it and press the &quot;delete&quot; or the &quot;d&quot; key on your keyboard.')">Delete a variable</a></li>
                <li><a href="#" onclick="how('To delete a connection, click/tap on the start variable and then on the end variable.')">Delete a connection</a></li>
                <!-- <li><a href="#" onclick="how('To save the model, copy-paste the content of the &quot;model code&quot; into your favourite text editor (e.g. &quot;Word&quot;).')">Save a model</a></li> -->
                <!-- <li><a href="#" onclick="how('To set the exposure variable, click/tap it and check &quot;exposure&quot; in the &quot;variable&quot; tab, or move the mouse over it and press the &quot;e&quot; key on your keyboard.')">Set exposure variable</a></li> -->
                <!-- <li><a href="#" onclick="how('To set the outcome variable, click/tap it and check &quot;outcome&quot; in the &quot;variable&quot; tab, or move the mouse over it and press the &quot;o&quot; key on your keyboard.')">Set outcome variable</a></li> -->
                <!-- <li><a href="#" onclick="how('To toggle adjustment for a variable, click/tap it and check &quot;adjusted&quot in the &quot;variable&quot; tab, or move the mouse over it and press the &quot;a&quot; key on your keyboard.')">Adjust for variable</a></li> -->
                <!-- <li><a href="#" onclick="how('To toggle observed status for a variable, click/tap it and check &quot;unobserved&quot; in the &quot;variable&quot; tab, or move the mouse over it and press the &quot;u&quot; key on your keyboard.')">Make a variable unobserved (latent)</a></li> -->
            </ul>
        </li>
        <!--
        <li><a href="javascript:void(0)" id="menu_layout_title">Layout</a>
            <ul id="menu_layout" style="z-Index:12">
                <li><a href="#" onclick="generateSpringLayout();displayHide('menu_layout');return false">Generate layout automatically</a></li>
            </ul>
        </li>
        <li style="border-right:none"><a href="javascript:void(0)" id="menu_help_title">Help</a>
            <ul id="menu_help" style="z-Index:11">
                <li><a href="manual-3.x.pdf" target="_blank" onclick="displayHide('menu_help')">Manual</a></li>
                <li><a href="http://dagitty.net/learn/" target="_blank" onclick="displayHide('menu_help')">Other Resources</a></li>
            </ul>
        </li>
        <li style="float:none; overflow: hidden; border-right: none;"> &nbsp;</li>
        -->
    </ul>

    <!-- default dag is our calculator model -->
    <div id="canvas" class="dagitty" data-mutable="true">
        dag {
        bb="-0.5,-0.5,0.5,0.5"
        Beh0 [answer,answerlabel="0 Personen",answervalue="0",answersort="0",pos="0.308,-0.472"]
        Beh1 [answer,answerlabel="1 Person",answervalue="1",answersort="1",pos="0.327,-0.420"]
        Beh2 [answer,answerlabel="2 Personen",answervalue="2",answersort="2",pos="0.338,-0.371"]
        Beh3 [answer,answerlabel="3 Personen",answervalue="3",answersort="3",pos="0.332,-0.308"]
        Beh4 [answer,answerlabel="4 Personen",answervalue="4",answersort="4",pos="0.325,-0.231"]
        Beh5 [answer,answerlabel="5 Personen",answervalue="5",answersort="5",pos="0.315,-0.150"]
        EinkommenKind1 [input,pos="0.164,-0.434"]
        EinkommenKind2 [input,pos="0.157,-0.368"]
        EinkommenKind3 [input,pos="0.148,-0.297"]
        EinkommenKind4 [input,pos="0.150,-0.231"]
        EinkommenKind5 [input,pos="0.141,-0.175"]
        EinkommenPers1 [input,inputsort="1",pos="-0.208,-0.468"]
        EinkommenPers2 [input,inputsort="2",pos="-0.204,-0.404"]
        EinkommenPers3 [input,inputsort="3",pos="-0.203,-0.337"]
        EinkommenPers4 [input,inputsort="4",pos="-0.203,-0.271"]
        EinkommenPers5 [input,inputsort="5",pos="-0.204,-0.206"]
        Erwachsene [question,questionlabel="Erwachsene im Haushalt",pos="-0.464,-0.331"]
        Erwachsene1 [answer,answerlabel="1 Erwachsener",answervalue="1",pos="-0.381,-0.457"]
        Erwachsene2 [answer,answerlabel="2 Erwachsene",answervalue="2",pos="-0.377,-0.391"]
        Erwachsene3 [answer,answerlabel="3 Erwachsene",answervalue="3",pos="-0.379,-0.335"]
        Erwachsene4 [answer,answerlabel="4 Erwachsene",answervalue="4",pos="-0.377,-0.282"]
        Erwachsene5 [answer,answerlabel="5 Erwachsene",answervalue="5",pos="-0.380,-0.209"]
        GeldAllein [result,resultcalculation="(richtsatz-EinkommenPers1)*.6",pos="-0.241,0.326"]
        GeldPers1 [result,resultcalculation="(richtsatzhaushalt-EinkommenPers1)*.6",pos="-0.243,0.255"]
        GeldPers2 [result,resultcalculation="(richtsatzhaushalt-EinkommenPers2)*.6",pos="-0.242,0.183"]
        GeldPers3 [result,resultcalculation="(richtsatzdritte-EinkommenPers3)*.6",pos="-0.237,0.111"]
        GeldPers4 [result,resultcalculation="(richtsatzdritte-EinkommenPers4)*.6",pos="-0.228,0.036"]
        GeldPers5 [result,resultcalculation="(richtsatzdritte-EinkommenPers5)*.6",pos="-0.222,-0.032"]
        Geldleistung [result,resultcalculation="GeldPers1*1",pos="-0.026,0.316"]
        Gesamtleistung [result,resultcalculation="Geldleistung+Sachleistung",pos="-0.023,0.138"]
        Heizung [input,pos="0.444,-0.154"]
        Kind0 [answer,answerlabel="0 Kinder",answervalue="0",pos="-0.020,-0.480"]
        Kind1 [answer,answerlabel="1 Kind",answervalue="1",pos="0.003,-0.432"]
        Kind2 [answer,answerlabel="2 Kinder",answervalue="2",pos="0.007,-0.379"]
        Kind3 [answer,answerlabel="3 Kinder",answervalue="3",pos="0.000,-0.331"]
        Kind4 [answer,answerlabel="4 Kinder",answervalue="4",pos="-0.007,-0.280"]
        Kind5 [answer,answerlabel="5 Kinder",answervalue="5",pos="-0.025,-0.206"]
        Miete [input,pos="0.442,-0.257"]
        Minderjaehrige [question,questionlabel="TestTest",pos="-0.091,-0.337"]
        PersBeh [question,pos="0.267,-0.349"]
        SachAllein [result,resultcalculation="(richtsatz-EinkommenPers1)*.4-(Wohnzuschuss/Erwachsene)",pos="-0.129,-0.027"]
        SachPers1 [result,resultcalculation="(richtsatzhaushalt-EinkommenPers1)*.4-(Wohnzuschuss/Erwachsene)",pos="-0.125,0.041"]
        SachPers2 [result,resultcalculation="(richtsatzhaushalt-EinkommenPers2)*.4-(Wohnzuschuss/Erwachsene)",pos="-0.125,0.123"]
        SachPers3 [result,resultcalculation="(richtsatzdritte-EinkommenPers3)*.4-(Wohnzuschuss/Erwachsene)",pos="-0.128,0.203"]
        SachPers4 [result,resultcalculation="(richtsatzdritte-EinkommenPers4)*.4-(Wohnzuschuss/Erwachsene)",pos="-0.127,0.287"]
        SachPers5 [result,resultcalculation="(richtsatzdritte-EinkommenPers5)*.4-(Wohnzuschuss/Erwachsene)",pos="-0.129,0.393"]
        Sachleistung [result,resultcalculation="SachPers1*1",pos="-0.022,0.221"]
        Strom [input,pos="0.444,-0.046"]
        Wohnaufwand [result,resultcalculation="Miete+Heizung+Strom",pos="-0.031,0.399"]
        Wohnzuschuss [input,pos="0.441,-0.352"]
        alleinlebensunterhalt [result,resultcalculation="richtsatz*.6",pos="0.324,-0.041"]
        alleinwohnbedarf [result,resultcalculation="richtsatz*.4",pos="0.328,0.071"]
        decision1 [decision,decisioncalculation="!model.Erwachsene || model.Erwachsene < 1",pos="-0.288,-0.466"]
        decision2 [decision,decisioncalculation="!model.Erwachsene || model.Erwachsene < 2",pos="-0.283,-0.403"]
        decision3 [decision,decisioncalculation="!model.Erwachsene || model.Erwachsene < 3",pos="-0.288,-0.338"]
        decision4 [decision,decisioncalculation="!model.Erwachsene || model.Erwachsene < 4",pos="-0.295,-0.278"]
        decision5 [decision,decisioncalculation="!model.Erwachsene || model.Erwachsene < 5",pos="-0.304,-0.207"]
        drittelebensunterhalt [result,resultcalculation="richtsatzdritte*.6",pos="0.039,-0.056"]
        drittewohnbedarf [result,resultcalculation="richtsatzdritte*.4",pos="0.047,0.063"]
        haushaltlebensunterhalt [result,resultcalculation="richtsatzhaushalt*.6",pos="0.197,-0.059"]
        haushaltwohnbedarf [result,resultcalculation="richtsatzhaushalt*.4",pos="0.196,0.081"]
        kinddecision1 [decision,decisioncalculation="!model.Minderjaehrige || model.Minderjaehrige < 1",pos="0.072,-0.442"]
        kinddecision2 [decision,decisioncalculation="!model.Minderjaehrige || model.Minderjaehrige < 2",pos="0.078,-0.379"]
        kinddecision3 [decision,decisioncalculation="!model.Minderjaehrige || model.Minderjaehrige < 3",pos="0.069,-0.322"]
        kinddecision4 [decision,decisioncalculation="!model.Minderjaehrige || model.Minderjaehrige < 4",pos="0.056,-0.266"]
        kinddecision5 [decision,decisioncalculation="!model.Minderjaehrige || model.Minderjaehrige < 5",pos="0.042,-0.199"]
        kinder1 [result,resultcalculation="richtsatz*.25",pos="-0.452,-0.051"]
        kinder2 [result,resultcalculation="richtsatz*.20",pos="-0.453,0.057"]
        kinder3 [result,resultcalculation="richtsatz*.15",pos="-0.453,0.176"]
        kinder4 [result,resultcalculation="richtsatz*.125",pos="-0.452,0.295"]
        kinder5 [result,resultcalculation="richtsatz*.12",pos="-0.456,0.407"]
        richtsatz [input,inputdefaultvalue="917.35",pos="0.453,0.203"]
        richtsatzdritte [result,resultcalculation="richtsatz*.45",pos="0.235,0.196"]
        richtsatzhaushalt [result,resultcalculation="richtsatz*.7",pos="0.358,0.206"]
        zuschlagPersBeh [result,resultcalculation="PersBeh*zuschlagbeh",pos="-0.239,0.388"]
        zuschlagalleink1 [result,resultcalculation="richtsatz*.12",pos="-0.347,0.027"]
        zuschlagalleink2 [result,resultcalculation="richtsatz*.09",pos="-0.351,0.139"]
        zuschlagalleink3 [result,resultcalculation="richtsatz*.06",pos="-0.351,0.235"]
        zuschlagalleink4 [result,resultcalculation="richtsatz*.03",pos="-0.351,0.333"]
        zuschlagbeh [result,resultcalculation="richtsatz*.18",pos="0.144,0.190"]
        Beh0 -> Wohnzuschuss
        Beh1 -> Wohnzuschuss
        Beh2 -> Wohnzuschuss
        Beh3 -> Wohnzuschuss
        Beh4 -> Wohnzuschuss
        Beh5 -> Wohnzuschuss
        EinkommenKind1 -> EinkommenKind2
        EinkommenKind1 -> PersBeh
        EinkommenKind2 -> EinkommenKind3
        EinkommenKind2 -> PersBeh
        EinkommenKind3 -> EinkommenKind4
        EinkommenKind3 -> PersBeh
        EinkommenKind4 -> EinkommenKind5
        EinkommenKind4 -> PersBeh
        EinkommenKind5 -> PersBeh
        EinkommenPers1 -> EinkommenPers2
        EinkommenPers1 -> Minderjaehrige
        EinkommenPers2 -> EinkommenPers3
        EinkommenPers2 -> Minderjaehrige
        EinkommenPers3 -> EinkommenPers4
        EinkommenPers3 -> Minderjaehrige
        EinkommenPers4 -> EinkommenPers5
        EinkommenPers4 -> Minderjaehrige
        EinkommenPers5 -> Minderjaehrige
        Erwachsene -> Erwachsene1
        Erwachsene -> Erwachsene2
        Erwachsene -> Erwachsene3
        Erwachsene -> Erwachsene4
        Erwachsene -> Erwachsene5
        Erwachsene1 -> decision1
        Erwachsene2 -> decision2
        Erwachsene3 -> decision3
        Erwachsene4 -> decision4
        Erwachsene5 -> decision5
        GeldAllein -> GeldPers1
        GeldPers1 -> GeldPers2
        GeldPers2 -> GeldPers3
        GeldPers3 -> GeldPers4
        GeldPers4 -> GeldPers5
        GeldPers5 -> SachAllein
        Geldleistung -> Sachleistung
        Heizung -> Strom
        Kind0 -> PersBeh [pos="0.224,-0.497"]
        Kind1 -> kinddecision1
        Kind2 -> kinddecision2
        Kind3 -> kinddecision3
        Kind4 -> kinddecision4
        Kind5 -> kinddecision5
        Miete -> Heizung
        Minderjaehrige -> Kind0
        Minderjaehrige -> Kind1
        Minderjaehrige -> Kind2
        Minderjaehrige -> Kind3
        Minderjaehrige -> Kind4
        Minderjaehrige -> Kind5
        PersBeh -> Beh0
        PersBeh -> Beh1
        PersBeh -> Beh2
        PersBeh -> Beh3
        PersBeh -> Beh4
        PersBeh -> Beh5
        SachAllein -> SachPers1
        SachPers1 -> SachPers2
        SachPers2 -> SachPers3
        SachPers3 -> SachPers4
        SachPers4 -> SachPers5
        SachPers5 -> Wohnaufwand
        Sachleistung -> Gesamtleistung
        Strom -> alleinlebensunterhalt
        Wohnaufwand -> Geldleistung
        Wohnzuschuss -> Miete
        alleinlebensunterhalt -> alleinwohnbedarf
        alleinwohnbedarf -> haushaltlebensunterhalt
        decision1 -> EinkommenPers1
        decision2 -> EinkommenPers2
        decision3 -> EinkommenPers3
        decision4 -> EinkommenPers4
        decision5 -> EinkommenPers5
        drittelebensunterhalt -> drittewohnbedarf
        drittewohnbedarf -> kinder1 [pos="-0.149,-0.202"]
        haushaltlebensunterhalt -> haushaltwohnbedarf
        haushaltwohnbedarf -> drittelebensunterhalt
        kinddecision1 -> EinkommenKind1
        kinddecision2 -> EinkommenKind2
        kinddecision3 -> EinkommenKind3
        kinddecision4 -> EinkommenKind4
        kinddecision5 -> EinkommenKind5
        kinder1 -> kinder2
        kinder2 -> kinder3
        kinder3 -> kinder4
        kinder4 -> kinder5
        kinder5 -> zuschlagalleink1
        zuschlagPersBeh -> GeldAllein
        zuschlagalleink1 -> zuschlagalleink2
        zuschlagalleink2 -> zuschlagalleink3
        zuschlagalleink3 -> zuschlagalleink4
        zuschlagalleink4 -> zuschlagPersBeh
        }





    </div>

</main>

<aside>

    <div id="llegend">

        <h3 id="variable_header" onclick="displayToggle('variable')"><img src="images/arrow-down.png" alt="" id="a_variable">
            Variable</h3>
        <div id="variable" style="display:block">

            <form autocomplete="off">
                <p>
                    <span>Unique ID: </span><span id="variable_label" style="font-weight:bold"></span>
                </p>

                <hr>
                <span>Typ</span>
                <p><input type="checkbox" checked="checked" id="variable_question"
                          onclick="GUI.set_variable_status('question',this.checked)" />
                    <label for="variable_question">Frage (Buttons)</label></p>

                    <label id="variable_question_label_label" class="textboxlabel" for="variable_question_label">Label </label>
                    <input type="text" id="variable_question_label" name="variable_question_label" placeholder="label" oninput="GUI.set_question_label();" />
                <p><input type="checkbox" checked="checked" id="variable_input"
                          onclick="GUI.set_variable_status('input',this.checked)" />
                    <label for="variable_input">Eingabefeld (Zahlen)</label></p>

                    <label id="variable_input_label_label" class="textboxlabel" for="variable_input_label">Label </label>
                    <input type="text" id="variable_input_label" name="variable_input_label" placeholder="label" oninput="GUI.set_input_label();" />

                    <label id="variable_input_defaultvalue_label" class="textboxlabel" for="variable_input_defaultvalue">Default Value </label>
                    <input type="text" id="variable_input_defaultvalue" name="variable_input_defaultvalue" placeholder="default value" oninput="GUI.set_input_defaultvalue();" />

                    <label id="variable_input_sort_label" class="textboxlabel" for="variable_input_sort">Sort </label>
                    <input type="number" min="0" id="variable_input_sort" name="variable_input_sort" placeholder="0" oninput="GUI.set_input_sort();" />
                <p><input type="checkbox" checked="checked" id="variable_answer"
                          onclick="GUI.set_variable_status('answer',this.checked)" />
                    <label for="variable_answer">Antwortmöglichkeit</label><br />

                    <label id="variable_answer_label_label" class="textboxlabel" for="variable_answer_label">Label </label>
                    <input type="text" id="variable_answer_label" name="variable_answer_label" placeholder="label" oninput="GUI.set_answer_label();" />

                    <label id="variable_answer_value_label" class="textboxlabel" for="variable_answer_value">Value </label>
                    <input type="text" id="variable_answer_value" name="variable_answer_value" placeholder="value" oninput="GUI.set_answer_value();" />

                    <label id="variable_answer_sort_label" class="textboxlabel" for="variable_answer_sort">Sort </label>
                    <input type="number" min="0" id="variable_answer_sort" name="variable_answer_sort" placeholder="0" oninput="GUI.set_answer_sort();" />
                </p>
                <p><input type="checkbox" checked="checked" id="variable_decision"
                          onclick="GUI.set_variable_status('decision',this.checked)" />
                    <label for="variable_decision">Berechnung (Entscheidung)</label><br />

                    <label id="variable_decision_calculation_label" class="textboxlabel" for="variable_decision_calculation">Formel </label>
                    <input type="text" id="variable_decision_calculation" name="variable_decision_calculation" placeholder="label" oninput="GUI.set_decision_calculation();" />
                </p>
                <p><input type="checkbox" checked="checked" id="variable_result"
                          onclick="GUI.set_variable_status('result',this.checked)" />
                    <label for="variable_result">Ergebnis</label></p>

                    <label class="variable_result_label_label" class="textboxlabel" for="variable_result_label">Label </label>
                    <input type="text" id="variable_result_label" name="variable_result_label" placeholder="label" oninput="GUI.set_result_label();" />

                    <label for="variable_result_calculation">Formel </label>
                    <textarea id="variable_result_calculation" name="variable_result_calculation" placeholder="formula" rows="5" cols="33" oninput="GUI.set_result_calculation();"></textarea>
                <hr>
                    <label for="variable_addl_info">Add'l Info</label>
                    <textarea id="variable_addl_info" name="variable_addl_info" placeholder="label" rows="5" cols="33" oninput="GUI.set_addl_info();"></textarea>

                <input type="hidden" id="variable_id" value="">

                <p>
                    <button type="button" onclick="DAGittyControl.deleteVertex(document.getElementById('variable_id').value)">delete</button>
                    <button type="button" onclick="DAGittyControl.getView().renameVertexDialog(document.getElementById('variable_id').value)">rename</button>
                </p>

            </form>



        </div>


        <h3 id="viewmode_header" onclick="displayToggle('viewmode')"><img src="images/arrow-down.png" alt="" id="a_view">
            View mode</h3>
        <div id="viewmode" style="display:block">

            <form autocomplete="off">
                <p><input type="radio" checked="checked" id="dagview_dag" name="dagview"
                          onclick="GUI.set_view_mode('normal')" />
                    <label for="dagview_dag">normal</label></p>
                <p><input type="radio" name="dagview" id="dagview_moral"
                          onclick="GUI.set_view_mode('moral')"/>
                    <label for="dagview_moral">moral graph</label></p>
                <p><input type="radio" name="dagview" id="dagview_dependency"
                          onclick="GUI.set_view_mode('dependency')"/>
                    <label for="dagview_dependency">correlation graph</label></p>
                <p><input type="radio" name="dagview" id="dagview_equivalence"
                          onclick="GUI.set_view_mode('equivalence')"/>
                    <label for="dagview_equivalence">equivalence class</label></p>
            </form>

        </div>

        <h3 id="effects_header" onclick="displayToggle('effects')"><img src="images/arrow-down.png" alt="" id="a_effects"> Effect analysis</h3>
        <div id="effects" style="display:block">
            <form autocomplete="off">
                <p><input type="checkbox" id="highlight_puredirect"
                          onclick="GUI.set_highlight_puredirect(this.checked)"/>
                    <label for="highlight_puredirect">atomic direct effects</label></p>
            </form>
        </div>

        <h3 id="dagstyle_header" onclick="displayToggle('dagstyle')"><img src="images/arrow-down.png" alt="" id="a_view">
            Diagram style</h3>
        <div id="dagstyle" style="display:block">
            <form autocomplete="off">
                <p><input type="radio" checked="checked" id="dagstyle_original" name="dagstyle"
                          onclick="GUI.set_style('original')" />
                    <label for="dagstyle_original">classic</label></p>
                <p><input type="radio" name="dagstyle" id="dagstyle_semlike"
                          onclick="GUI.set_style('semlike')"/>
                    <label for="dagstyle_semlike">SEM-like</label></p>
            </form>
        </div>

        <h3 id="coloring_header" onclick="displayToggle('coloring')"><img src="images/arrow-down.png" alt="" id="a_view"> Coloring</h3>
        <div id="coloring" style="display:block">
            <form autocomplete="off">
                <p><input type="checkbox" checked="checked" id="highlight_causal"
                          onclick="GUI.set_highlight_causal(this.checked)" />
                    <label for="highlight_causal">causal paths</label></p>
                <p><input type="checkbox" checked="checked" id="highlight_biasing"
                          onclick="GUI.set_highlight_biasing(this.checked)"/>
                    <label for="highlight_biasing">biasing paths</label></p>
                <p><input type="checkbox" checked="checked" id="highlight_ancestral"
                          onclick="GUI.set_highlight_ancestors(this.checked)"/>
                    <label for="highlight_ancestral">ancestral structure</label></p>
            </form>
        </div>



        <h3 id="legend_header" onclick="displayToggle('legend')"><img src="images/arrow-down.png" alt="" id="a_legend"> Legend</h3>
        <div id="legend" style="display:block">
            <p>
                <img id="liquestion" src="images/legend/social/question.png" alt="" />
                <span>Frage</span>
            </p>
            <p>
                <img id="liinput" src="images/legend/social/input.png" alt="" />
                <span>Eingabefeld</span>
            </p>
            <p>
                <img id="liinput" src="images/legend/social/radio.png" alt="" />
                <span>Antwortmöglichkeit</span>
            </p>
            <p>
                <img id="lidecision" src="images/legend/social/decision.png" alt="" />
                <span>Entscheidung</span>
            </p>
            <p>
                <img id="lidecision" src="images/legend/social/result.png" alt="" />
                <span>Ergebnis</span>
            </p>
        </div>

    </div>


    <div id="rlegend">
        <h3 id="causal_effect_header" onclick="displayToggle('causal_effect')">
            <img src="images/arrow-down.png" alt="" id="a_causal_effect" />
            Causal effect identification
        </h3>
        <p id="causal_effect_select">
            <select id="causal_effect_kind" onchange="causalEffectEstimates()">
                <option value="adj_total">Adjustment (total effect)</option>
                <option value="adj_direct">Adjustment (direct effect)</option>
                <option value="instrument">Instrumental variable</option>
            </select>
        </p>

        <div id="causal_effect" style="display:block">

        </div>

        <h3 id="testable_implications_header" onclick="displayToggle('testable_implications')"><img src="images/arrow-down.png" alt="" id="a_testable_implications" />  Testable implications</h3>

        <div id="testable_implications" style="display:block">

        </div>

        <h3 id="model_data_header" onclick="displayToggle('model_data')"><img src="images/arrow-down.png" alt="" id="a_model_data" />  Model code</h3>
        <div id="model_data" style="display:block">
            <form name="model_data_frm">
      <textarea rows="10" cols="35" name="adj_matrix" id="adj_matrix"
                onkeydown="if(this.value != Model.dag_model_text_data){ displayShow('model_refresh');this.style.backgroundColor='#fec'; }"></textarea>
            </form>
            <p id="model_refresh" style="display:none">You have modified the model code. To
                draw the modified model, click here: <button onclick="loadDAGFromTextData()">Update DAG</button></p>
        </div>

        <h3 id="php_test_header" onclick="displayToggle('php_test')"><img src="images/arrow-down.png" alt="" id="a_php_test" />Update SOCIAL</h3>
        <div id="php_test" style="display:block">
            <label class="textboxlabel" for="form_name">Form Name</label>
            <input type="text" id="form_name" name="form_name" placeholder="form_name">

            <p id="php_test_text" style="display:block">To update & save your form, click one of the buttons:</p>
            <button onclick="myAJAX('calculator')">Generate Calculator & Save</button>
            <button onclick="myAJAX('questionnaire')">Generate Questionnaire & Save</button>



            <p style="word-break: break-word;">You may optionally specify a form_name for your form, it will then be reachable
                at https://social.cosy.univie.ac.at/form_name<br />Saved models can be loaded after <a href=".">refreshing</a> this page.
            With no name entered, it will be reachable at https://social.cosy.univie.ac.at/test</p>


        </div>

        <h3 id="summary_header" onclick="displayToggle('summary')"><img src="images/arrow-down.png" alt="" id="a_summary"> Summary</h3>
        <div id="summary" style="display:block">
            <div id="info_cycle"> </div>
            <table id="info_summary">
                <tr><td>exposure(s)</td><td class="info" id="info_exposure">not set</td></tr>
                <tr><td>outcome(s)</td><td class="info" id="info_outcome">not set</td></tr>
                <tr><td>covariates</td><td class="info" id="info_covariates">0</td></tr>
                <tr><td>causal paths</td><td class="info" id="info_frontdoor">0</td></tr>
            </table>
        </div>

    </div>

</aside>


<div id="export" style="display: none;">
    <form name="exportform" id="exportform" action="http://www.dagitty.net/pdf/pdf.php" method="POST" target="_blank">
        <input type="hidden" name="exportformsvg" id="exportformsvg" />
    </form>
</div>
</body>
</html>
