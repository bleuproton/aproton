/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var CustomerCompanySelectComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var mediator = require('oroui/js/mediator');

    CustomerCompanySelectComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            valueSelector: 'input[name$="[company]"]',
            textSelector: 'span[class="select2-chosen"]',
            attribute: 'company'
        },
        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.$valueElement = $(this.options._sourceElement).find(this.options.valueSelector);

            this.$valueElement.on('change', _.bind(this.updateData, this));
            if (this.$valueElement.val()) {
                this.updateData();
            }
            this.$valueElement.trigger('change');
        },

        /**
         * Handle company selection
         */
        updateData: function() {
            var value = this.$valueElement.val();
            var changes = {};
            changes.from = this.getData() || {};
            this.saveData({});
            var storedData = this.getData();

            if (value !== undefined) {
                storedData['id'] = value;
                storedData['name'] = $(this.options._sourceElement).find(this.options.textSelector).text();
            } else {
                storedData = changes.from;
            }
            this.saveData(storedData);
            changes.to = storedData;

            if (changes.to !== changes.from) {
                this.triggerChangeEvent(changes);
            }
        },

        /**
         * Return units from data attribute
         *
         * @returns {jQuery.Element}
         */
        getData: function() {
            return this.options._sourceElement.data(this.options.attribute) || {};
        },

        /**
         * Save data to data attribute
         *
         * @param {Object} data
         */
        saveData: function(data) {
            this.options._sourceElement.data(this.options.attribute, data);
        },

        /**
         * Trigger add event
         *
         * @param {Object} data
         */
        triggerChangeEvent: function(data) {
            mediator.trigger('marello_customer:company:changed', data);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.options._sourceElement.off();
            CustomerCompanySelectComponent.__super__.dispose.call(this);
        }
    });

    return CustomerCompanySelectComponent;
});
