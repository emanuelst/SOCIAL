import {Component, HostListener, Inject, OnInit} from '@angular/core';
import {FormBuilder, FormGroup} from '@angular/forms';
import {FormlyFieldConfig, FormlyFormOptions} from '@ngx-formly/core';
import {TranslateService} from '@ngx-translate/core';
import {UserService} from './user.service';
import {KeyValue} from '@angular/common';
import { DOCUMENT } from '@angular/common';

import {faInfoCircle, faEuroSign} from '@fortawesome/free-solid-svg-icons';


@Component({
    selector: 'app-root',
    templateUrl: './app.component.html',
    styleUrls: ['./app.component.css']
})

// https://stackoverflow.com/questions/35763730/difference-between-constructor-and-ngoninit
export class AppComponent implements OnInit {

    resultCalculationDictionary = {};
    resultCalculationFormulas = {};

    faInfoCircle = faInfoCircle;
    faEuroSign = faEuroSign;


    title = 'social';
    form = new FormGroup({});
    options: FormlyFormOptions = {};

    model: any = {};
    fields: FormlyFieldConfig[];

    languages = [{
        name: 'English',
        code: 'en'
    }, {
        name: 'Deutsch',
        code: 'de'
    }
    ];
    languageForm: FormGroup;

    // https://codepen.io/pixelthing/pen/LIqsg
    // https://stackoverflow.com/questions/9712295/disable-scrolling-on-input-type-number
    // https://stackoverflow.com/questions/55378766/angular-material-input-number-spinner-scrolling-problem
    // https://stackoverflow.com/questions/50928006/angular-2-event-listener-on-document-inside-a-component
    // https://gist.github.com/pererinha/aaef044b021bbf7372e5
    // https://stackoverflow.com/questions/9712295/disable-scrolling-on-input-type-number#comment92922450_51076231
    // https://stackoverflow.com/questions/4878484/difference-between-tagname-and-nodename
    // https://dzone.com/articles/what-are-hostbinding-and-hostlistener-in-angular
    // only prevent default when input type number has focus & the wheel element targets the same element
    // TODO: this is global, should bind to element
    private formtype: any;

    @HostListener('wheel', ['$event'])
    onWheel(event) {
        const activeEl = (document.activeElement as HTMLInputElement);
        const targetEl = (event.target as HTMLInputElement);

        if (activeEl.type === 'number' && targetEl.nodeName === 'INPUT' && activeEl === targetEl) {
            event.preventDefault();
        }

    }

    // https://formly.dev/examples/advanced/i18n-alternative

    constructor(@Inject(DOCUMENT) private document: Document, private userService: UserService, public translate: TranslateService, private fb: FormBuilder) {
        // https://github.com/ngx-formly/ngx-formly/issues/390#issuecomment-306908053
        // https://stackoverflow.com/questions/34615425/how-to-watch-for-form-changes-in-angular/34616143#34616143
        this.form.valueChanges.subscribe(data => {
            // console.log('Form changes', data)
            this.submit();
        });

        this.userService.getUserData().subscribe(([model, fields]) => {
            this.model = model;
            this.formtype = fields.shift(); // consumes first element in array which contains our "myFormType"
            // we consume this to restore the this.fields form model
            // console.log(this.formtype)
            this.fields = fields;
        });

        // translate
        translate.addLangs(['en', 'de']);
        translate.setDefaultLang('en');

        const browserLang = translate.getBrowserLang();

        translate.use(browserLang.match(/en|de/) ? browserLang : 'en');
        this.model.lang = translate.currentLang;
    }

    ngOnInit() {
        this.languageForm = this.fb.group({
            languageControl: ['en']
        });

        // set lang attribute
        // https://stackoverflow.com/questions/53990773/angular-universal-html-lang-tag
        this.document.documentElement.lang = this.translate.currentLang;
    }

    // https://github.com/ngx-formly/ngx-formly/issues/1225#issuecomment-628118046
    modelChanges(model) {
        // console.log('model changes')
    }


    submit() {
        if (this.form.valid) {


            // find node with key "resultcalculation"
            const resultCalculationKey = 'resultcalculation';

            // filter array
            // TODO this filter yields all calculations as soon as they are shown once...
            const filterArray = this.fields.filter(o => o.hasOwnProperty(resultCalculationKey));

            // console.log(filterArray)
            // only get those where hide is either undefined or false... i.e omit those with hide == true
            // filterArray = filterArray.filter(i => i.hide !== true)

            // console.log(this.fields)
            // get array where the key= equals a key in the model array...
            // https://stackoverflow.com/questions/56262842/how-to-filter-a-dictionary-of-key-value-with-an-array-of-keys-in-javascript
            // get the array where the current modelState result variable is contained to get current calculation
            // //console.log(filterArray)

            // reset currentText
            // document.getElementById('resultCalc').innerText = ''

            // TODO also look up our resultCalculationDictionary (maybe have to loop twice...)
            // TODO then also use results in calculations... be aware of infinite loop potential ;)

            this.resultCalculationDictionary = {};
            this.resultCalculationFormulas = {};

            // for each currently visible calculation, do calculation and add to span element
            filterArray.forEach(calculation => {

                // @ts-ignore
                // remove leading and trailing quotes from String (we know there will be 1 leading and 1 trailing quote)
                const evalCalculation = calculation.resultcalculation.slice(1, -1);
                const curKey = calculation.key;

                // https://stackoverflow.com/questions/10610298/add-prefix-for-matches-in-string
                // prefix with calculation string with "this.model"
                const prefixedCalculation = evalCalculation.replace(/([a-zA-Z]+)/g, 'this.model.$1');

                // TODO do not use eval!
                // TODO modify for multiple calcs... and show calculation
                // https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/eval#Never_use_eval!
                // console.log(prefixedCalculation)
                try {
                    // tslint:disable-next-line:no-eval
                    this.resultCalculationDictionary[curKey] = eval(prefixedCalculation);

                } catch (e) {
                    // console.log('whoops, eval failed' + e)
                }
                // document.getElementById('resultCalc').innerText += curKey + ' (' + evalCalculation + '): ' + result;
                // document.getElementById('resultCalc').innerHTML += '<br />'
                // alert(result);

            });

            // do calculation again w/ results obtained previously, to 'fill in gaps'
            filterArray.forEach(calculation => {
                // @ts-ignore
                const evalCalculation = calculation.resultcalculation.slice(1, -1);
                const curKey = calculation.key;
                const prefixedCalculation = evalCalculation.replace(/([a-zA-Z]+)/g, 'this.model.$1');
                // tslint:disable-next-line:no-eval
                try {
                    // tslint:disable-next-line:no-eval
                    const result = eval(prefixedCalculation);

                    // TODO https://stackoverflow.com/questions/42654595/how-to-check-if-value-is-nan
                    if (isNaN(result)) {
                        // //console.log("isnan")
                        // https://stackoverflow.com/a/11199608/841052
                        // https://stackoverflow.com/questions/11199565/replace-keys-in-string-with-value-from-keyvalue-pair-array-in-javascript
                        // replace "calculationstring" using "key" and replace using
                        let newstr = evalCalculation;

                        for (const key in this.resultCalculationDictionary) {
                            if (!this.resultCalculationDictionary.hasOwnProperty(key)) {
                                continue;
                            }

                            newstr = newstr.replace(key, this.resultCalculationDictionary[key]);
                        }

                        // console.log(newstr)
                        try {
                            // tslint:disable-next-line:no-eval no-shadowed-variable
                            const result = eval(newstr);
                            // console.log(result)
                            this.resultCalculationDictionary[curKey] = result;
                        } catch (e) {
                            // console.log('');
                        }

                    }

                } catch (e) {
                    // console.log('whoops, eval failed' + e)
                }
            });


            // do calculation a third time and look up both ways
            // do calculation again w/ results obtained previously, to 'fill in gaps'
            filterArray.forEach(calculation => {
                // @ts-ignore
                const evalCalculation = calculation.resultcalculation.slice(1, -1);

                const curKey = calculation.key;
                this.resultCalculationFormulas[curKey] = evalCalculation;

                const prefixedCalculation = evalCalculation.replace(/([a-zA-Z]+)/g, 'this.model.$1');
                // tslint:disable-next-line:no-eval
                try {
                    // tslint:disable-next-line:no-eval
                    const result = eval(prefixedCalculation);

                    // TODO https://stackoverflow.com/questions/42654595/how-to-check-if-value-is-nan

                    // //console.log("isnan")
                    // https://stackoverflow.com/a/11199608/841052
                    // https://stackoverflow.com/questions/11199565/replace-keys-in-string-with-value-from-keyvalue-pair-array-in-javascript
                    // replace "calculationstring" using "key" and replace using
                    let newstr = evalCalculation;

                    for (const key in this.resultCalculationDictionary) {
                        if (!this.resultCalculationDictionary.hasOwnProperty(key)) {
                            continue;
                        }

                        newstr = newstr.replace(key, this.resultCalculationDictionary[key]);
                    }

                    // console.log(newstr)
                    // prefix again (!)
                    newstr = newstr.replace(/([a-zA-Z]+)/g, 'this.model.$1');
                    try {
                        // tslint:disable-next-line:no-eval no-shadowed-variable
                        const result = eval(newstr);
                        // console.log(result)
                        this.resultCalculationDictionary[curKey] = result;
                    } catch (e) {
                        // console.log('');
                    }
                } catch (e) {
                    // console.log('whoops, eval failed' + e)
                }
            });


            /* TODO why do we need ts-ignore:
              https://stackoverflow.com/questions/18083389/ignore-typescript-errors-property-does-not-exist-on-value-of-type
              https://stackoverflow.com/questions/38324949/error-ts2339-property-x-does-not-exist-on-type-y
              https://stackoverflow.com/questions/51145180/how-to-use-ts-ignore-for-a-block (?)
            */

            // TODO https://stackoverflow.com/questions/42654595/how-to-check-if-value-is-nan
            // TODO issue with calculations & hideExpressions...
            // TODO, now using the resultCalculationDictionary, build another div with the final results
            // TODO: decisions in front of results...
            // (conditionally from model, somehow pass this info around)
        }
    }

    // TODO improve this calculation // generate dynamically

    get totalGeld(): number {
        if (this.model.Erwachsene === '1') {
            return this.resultCalculationDictionary['GeldAllein'];
        }
        if (this.model.Erwachsene === '2') {
            return this.resultCalculationDictionary['GeldPers1'] + this.resultCalculationDictionary['GeldPers2'];
        }
        if (this.model.Erwachsene === '3') {
            return this.resultCalculationDictionary['GeldPers1'] + this.resultCalculationDictionary['GeldPers2'] + this.resultCalculationDictionary['GeldPers3'];
        }
        if (this.model.Erwachsene === '4') {
            return this.resultCalculationDictionary['GeldPers1'] + this.resultCalculationDictionary['GeldPers2'] + this.resultCalculationDictionary['GeldPers3'] + this.resultCalculationDictionary['GeldPers4'];
        }
        if (this.model.Erwachsene === '5') {
            return this.resultCalculationDictionary['GeldPers1'] + this.resultCalculationDictionary['GeldPers2'] + this.resultCalculationDictionary['GeldPers3'] + this.resultCalculationDictionary['GeldPers4'] + this.resultCalculationDictionary['GeldPers5'];
        }
    }

    get totalSach(): number {
        if (this.model.Erwachsene === '1') {
            return this.resultCalculationDictionary['SachAllein']; // alleinstehend / alleinerzeihend
        }
        if (this.model.Erwachsene === '2') {
            return this.resultCalculationDictionary['SachPers1'] + this.resultCalculationDictionary['SachPers2'];
        }
        if (this.model.Erwachsene === '3') {
            return this.resultCalculationDictionary['SachPers1'] + this.resultCalculationDictionary['SachPers2'] + this.resultCalculationDictionary['SachPers3'];
        }
        if (this.model.Erwachsene === '4') {
            return this.resultCalculationDictionary['SachPers1'] + this.resultCalculationDictionary['SachPers2'] + this.resultCalculationDictionary['SachPers3'] + this.resultCalculationDictionary['SachPers4'];
        }
        if (this.model.Erwachsene === '5') {
            return this.resultCalculationDictionary['SachPers1'] + this.resultCalculationDictionary['SachPers2'] + this.resultCalculationDictionary['SachPers3'] + this.resultCalculationDictionary['SachPers4'] + this.resultCalculationDictionary['SachPers5'];
        }
    }

    get totalKinder(): number {

        if (this.model.Erwachsene) {
            if (this.model.Minderjaehrige === '0') {
                return 0;
            }
            if (this.model.Minderjaehrige === '1') {
                const kind1 = Math.max(0, this.resultCalculationDictionary['kinder1'] - this.model.EinkommenKind1);
                return kind1;
            }
            if (this.model.Minderjaehrige === '2') {
                const kind1 = Math.max(0, this.resultCalculationDictionary['kinder2'] - this.model.EinkommenKind1);
                const kind2 = Math.max(0, this.resultCalculationDictionary['kinder2'] - this.model.EinkommenKind2);

                return kind1 + kind2;
            }
            if (this.model.Minderjaehrige === '3') {
                const kind1 = Math.max(0, this.resultCalculationDictionary['kinder3'] - this.model.EinkommenKind1);
                const kind2 = Math.max(0, this.resultCalculationDictionary['kinder3'] - this.model.EinkommenKind2);
                const kind3 = Math.max(0, this.resultCalculationDictionary['kinder3'] - this.model.EinkommenKind3);
                return kind1 + kind2 + kind3;
            }
            if (this.model.Minderjaehrige === '4') {
                const kind1 = Math.max(0, this.resultCalculationDictionary['kinder4'] - this.model.EinkommenKind1);
                const kind2 = Math.max(0, this.resultCalculationDictionary['kinder4'] - this.model.EinkommenKind2);
                const kind3 = Math.max(0, this.resultCalculationDictionary['kinder4'] - this.model.EinkommenKind3);
                const kind4 = Math.max(0, this.resultCalculationDictionary['kinder4'] - this.model.EinkommenKind4);

                return kind1 + kind2 + kind3 + kind4;

            }
            if (this.model.Minderjaehrige === '5') {
                const kind1 = Math.max(0, this.resultCalculationDictionary['kinder5'] - this.model.EinkommenKind1);
                const kind2 = Math.max(0, this.resultCalculationDictionary['kinder5'] - this.model.EinkommenKind2);
                const kind3 = Math.max(0, this.resultCalculationDictionary['kinder5'] - this.model.EinkommenKind3);
                const kind4 = Math.max(0, this.resultCalculationDictionary['kinder5'] - this.model.EinkommenKind4);
                const kind5 = Math.max(0, this.resultCalculationDictionary['kinder5'] - this.model.EinkommenKind5);

                return kind1 + kind2 + kind3 + kind4 + kind5;
            }
        }

        return 0;

    }

    get totalKinderAllein(): number {
        if (this.model.Erwachsene === '1') {
            if (this.model.Minderjaehrige === '0') {
                return 0;
            }
            if (this.model.Minderjaehrige === '1') {
                return this.resultCalculationDictionary['zuschlagalleink1'];
            }
            if (this.model.Minderjaehrige === '2') {
                return this.resultCalculationDictionary['zuschlagalleink1'] + this.resultCalculationDictionary['zuschlagalleink2'];
            }
            if (this.model.Minderjaehrige === '3') {
                return this.resultCalculationDictionary['zuschlagalleink1'] + this.resultCalculationDictionary['zuschlagalleink2'] + this.resultCalculationDictionary['zuschlagalleink3'];
            }
            if (this.model.Minderjaehrige === '4') {
                return this.resultCalculationDictionary['zuschlagalleink1'] + this.resultCalculationDictionary['zuschlagalleink2'] + this.resultCalculationDictionary['zuschlagalleink3'] + this.resultCalculationDictionary['zuschlagalleink4'];
            }
            if (this.model.Minderjaehrige === '5') {
                return this.resultCalculationDictionary['zuschlagalleink1'] + this.resultCalculationDictionary['zuschlagalleink2'] + this.resultCalculationDictionary['zuschlagalleink3'] + this.resultCalculationDictionary['zuschlagalleink4'] + this.resultCalculationDictionary['zuschlagalleink4'];
            }
        }

        return 0;

    }

    get totalSachWohn(): number {
        return this.totalSach > this.resultCalculationDictionary['Wohnaufwand'] ? this.resultCalculationDictionary['Wohnaufwand'] : this.totalSach;
    }

    get totalZuschlagBeh(): number {
        if (this.model.PersBeh > 0) {
            return this.resultCalculationDictionary['zuschlagPersBeh'];
        } else {
            return 0;
        }
    }

    get totalAmount(): number {
        return this.totalGeld + this.totalSachWohn + this.totalKinder + this.totalKinderAllein + this.totalZuschlagBeh;
    }

    get totalGeldLeistungWithEverything(): number {
        return this.totalGeld + this.totalKinder + this.totalKinderAllein + this.totalZuschlagBeh;
    }

    changeLanguage(selectObject) {
        this.translate.use(selectObject);

        // set lang attribute
        // https://stackoverflow.com/questions/53990773/angular-universal-html-lang-tag
        // does not work on first change
        console.log(selectObject)
        //console.log(this.translate.currentLang);

        this.document.documentElement.lang = selectObject;

    }

    refresh(): void {
        window.location.reload();
    }


    // https://stackoverflow.com/questions/52793944/angular-keyvalue-pipe-sort-properties-iterate-in-order
    // Preserve original property order
    originalOrder = (a: KeyValue<number, string>, b: KeyValue<number, string>): number => {
        return 0;
    };


    // https://stackoverflow.com/questions/19074171/how-to-toggle-a-divs-visibility-by-using-a-button-click
    toggleDetailsButton(): void {
        const div = document.getElementById('innerresultCalc');
        div.style.display = div.style.display === 'none' ? 'block' : 'none';
        const button = document.getElementById('toggleDetailsButton');

        button.innerText = div.style.display === 'none' ? this.translate.instant('showdetails') : this.translate.instant('hidedetails');
    }

    showFormState(): void {
        if (this.form.valid) {
            alert(JSON.stringify(this.model));
        }
    }

    // https://stackoverflow.com/questions/53232018/how-to-set-condition-of-a-nan-value-to-show-hide-a-view-using-ngif-angular-6
    isNotANumber(value) {
        return Number.isNaN(value);
    }

    // return form type, either questionnaire or calculator as string, by default undefined
    getFormType() {
        if (this.formtype && this.formtype['myFormType']) { // if defined
            return this.formtype['myFormType'];
        } else { // by default return "undefined"
            return undefined;
        }
    }

    stepBack(): void {
        // index finding not based on length, some other way to determine cur question
        // get all formly fields without display none, get upmost of those, get ID of this one
        // get question/input/result from this.fields where hide is false

        // const selected = ['decision'];
        const output = this.fields.filter((obj) => obj.hide !== true); // only get nodes that are shown

        const currentlyShownNodes = [];

        // https://stackoverflow.com/questions/56867493/how-to-avoid-creating-references-of-this-in-es6-const-that-this-when-scope
        // https://stackoverflow.com/questions/1098040/checking-if-a-key-exists-in-a-javascript-object
        // https://stackoverflow.com/questions/9644044/javascript-this-pointer-within-nested-function
        // https://stackoverflow.com/questions/38823062/when-is-it-appropriate-to-use-a-semicolon
        // arrow function to access "this" in function
        output.forEach(item => {
            const isElementWithKeyLoop = (element) => element === item.key; // there may be more than one shown node :-D
            const currentIdx = Object.keys(this.model).findIndex(isElementWithKeyLoop);
            currentlyShownNodes.push(Object.keys(this.model)[currentIdx]);
        });

        const isElementWithKey = (element) => element === output[0].key; // there may be more than one shown node :-D
        const indexCurQ = Object.keys(this.model).findIndex(isElementWithKey);
        const indexPrevQ = indexCurQ - 1; // we need the actual node that led to "cur" node

        const modelObjPrevQ = Object.keys(this.model)[indexPrevQ];

        // https://stackoverflow.com/questions/17832583/create-an-object-with-dynamic-property-names
        // https://github.com/ngx-formly/ngx-formly/issues/1116
        // https://github.com/ngx-formly/ngx-formly/issues/1188

        // delete all currentlyShownNodes (cur question)
        currentlyShownNodes.forEach(item => delete this.model[item]);

        // update full model
        if (typeof modelObjPrevQ !== 'undefined') {
            this.model = {
                ...this.model,
                [modelObjPrevQ]: undefined // set previous to undefined
            };
        } else {
            this.model = {
                ...this.model,

            }
        }
    }

}
