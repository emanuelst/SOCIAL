window.addEventListener('DOMContentLoaded', (event) => {
    console.log('DOM fully loaded and parsed');
    //displayToggle('variable');

    displayToggle('viewmode');
    displayToggle('effects');
    displayToggle('dagstyle');
    displayToggle('coloring');
    displayToggle('legend');
    
    displayToggle('causal_effect');
    displayToggle('testable_implications');
    displayToggle('summary');
    
});

// https://stackoverflow.com/questions/3038901/how-to-get-the-response-of-xmlhttprequest
function myAJAX(type){
    let myname = document.getElementById("form_name").value
    let mytype = type

    /* default to test; as not to damage 'user-form' which is the default */
    // https://stackoverflow.com/questions/154059/how-can-i-check-for-an-empty-undefined-null-string-in-javascript
    if (!myname) {
        myname = 'test'
    }

    var xhr = new XMLHttpRequest()
    xhr.onreadystatechange = function() {
        if (xhr.readyState == XMLHttpRequest.DONE) {
            console.log(xhr.responseText);
        }
    }
    // https://stackoverflow.com/questions/8064691/how-do-i-pass-along-variables-with-xmlhttprequest
    xhr.open('POST', 'runParser.php?name=' + encodeURIComponent(myname) + '&type=' + encodeURIComponent(mytype))
    var mystring = document.getElementById("adj_matrix").value
    xhr.send(mystring)
}