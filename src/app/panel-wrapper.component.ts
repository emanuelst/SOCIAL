// https://formly.dev/guide/custom-formly-wrapper
// https://stackblitz.com/angular/dleylnmrbmd?file=app%2Fpanel-wrapper.component.ts

// https://stackoverflow.com/questions/31322525/confusing-duplicate-identifier-typescript-error-message

import { Component, ViewChild, ViewContainerRef } from '@angular/core';
import { FieldWrapper } from '@ngx-formly/core';

@Component({
    selector: 'app-formly-wrapper-panel',
    template: `
        <div class="card" *ngIf="to.addlinfo; else elseBlock">
            <pre class="caption">{{ to.addlinfo | translate}}</pre>
        </div>
        <ng-template #elseBlock></ng-template>
        <ng-container #fieldComponent></ng-container>
    `,
})
export class PanelWrapperComponent extends FieldWrapper {
    @ViewChild('fieldComponent', {read: ViewContainerRef}) fieldComponent: ViewContainerRef;
}

/*
Copyright 2018 Google Inc. All Rights Reserved.
Use of this source code is governed by an MIT-style license that
can be found in the LICENSE file at http://angular.io/license
*/
