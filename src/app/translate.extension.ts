import { FormlyFieldConfig } from '@ngx-formly/core';
import { TranslateService } from '@ngx-translate/core';
import {map} from 'rxjs/operators';

// https://formly.dev/examples/advanced/i18n-alternative

export class TranslateExtension {
    constructor(private translate: TranslateService) {}
    prePopulate(field: FormlyFieldConfig) {
        const to = field.templateOptions || {};
        if (!to.translate || to._translated) {
            return;
        }

        to._translated = true;
        field.expressionProperties = {
            ...(field.expressionProperties || {}),
            'templateOptions.label': this.translate.stream(to.label),
        };

        // https://github.com/ngx-formly/ngx-formly/issues/2134#issuecomment-596817520
        // translate "radio" buttons
        if (Array.isArray(field.templateOptions.options)) {
            const options = field.templateOptions.options;
            field.templateOptions.options = this.translate
                .stream(options.map(o => o.label)).pipe(
                    map(labels => {
                        return options.map(o => ({ ...o, label: labels[o.label] }));
                    })
                )
            ;
        }


    }
}

export function registerTranslateExtension(translate: TranslateService) {
    return {
        extensions: [{
            name: 'translate',
            extension: new TranslateExtension(translate)
        }],
    };
}
