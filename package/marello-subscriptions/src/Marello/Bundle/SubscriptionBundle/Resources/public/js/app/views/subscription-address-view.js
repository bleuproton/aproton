define(function(require) {
    'use strict';

    var SubscriptionAddressView;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export marellosubscriptions/js/app/views/subscription-address-view
     * @extends oroui.app.views.base.View
     * @class marellosubscriptions.app.views.SubscriptionAddressView
     */
    SubscriptionAddressView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            enterManuallyValue: '0',
            type: '',
            selectors: {
                address: '',
                subtotalsFields: []
            }
        },

        /**
         * @property {String}
         */
        ftid: '',

        /**
         * @property {jQuery}
         */
        $fields: null,

        /**
         * @property {jQuery}
         */
        $address: null,

        /**
         * @property {Boolean}
         */
        useDefaultAddress: null,

        /**
         * @property {Object}
         */
        fieldsByName: null,

        /**
         * @property {LoadingMaskView}
         */
        loadingMaskView: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});

            this.initLayout().done(_.bind(this.handleLayoutInit, this));

            this.loadingMaskView = new LoadingMaskView({container: this.$el});
            if (this.options.selectors.subtotalsFields.length > 0) {
                _.each(this.options.selectors.subtotalsFields, function(field) {
                    $(field).on('change', function() {
                        mediator.trigger('subscription:form-changes:trigger', {updateFields: ['possible_shipping_methods']});
                    });
                });
            }

            mediator.on('subscription:form-changes:trigger', this.loadingStart, this);
            mediator.on('subscription:form-changes:load', this.loadFormChanges, this);
        },

        /**
         * Doing something after loading child components
         */
        handleLayoutInit: function() {
            var self = this;

            this.ftid = this.$el.find('div[data-ftid]:first').data('ftid');

            this.useDefaultAddress = true;
            this.$fields = this.$el.find(':input[data-ftid]').filter(':not(' + this.options.selectors.address + ')');
            this.fieldsByName = {};
            this.$fields.each(function() {
                var $field = $(this);
                if ($field.val().length > 0) {
                    self.useDefaultAddress = false;
                }
                var name = self.normalizeName($field.data('ftid').replace(self.ftid + '_', ''));
                self.fieldsByName[name] = $field;
            });

            if (this.options.selectors.address) {
                this.setAddress(this.$el.find(this.options.selectors.address));

                this.customerAddressChange();
            } else {
                this._setReadOnlyMode(true);
            }
        },

        /**
         * Convert name with "_" to name with upper case, example: some_name > someName
         *
         * @param {String} name
         *
         * @returns {String}
         */
        normalizeName: function(name) {
            name = name.split('_');
            for (var i = 1, iMax = name.length; i < iMax; i++) {
                if (name[i]) {
                    name[i] = name[i][0].toUpperCase() + name[i].substr(1);
                }
            }
            return name.join('');
        },

        /**
         * Set new address element and bind events
         *
         * @param {jQuery} $address
         */
        setAddress: function($address) {
            this.$address = $address;

            var self = this;
            this.$address.change(function(e) {
                self.useDefaultAddress = false;
                self.customerAddressChange(e);
            });
        },

        /**
         * Implement customer address change logic
         */
        customerAddressChange: function(e) {
            if (this.$address.val() !== this.options.enterManuallyValue) {
                this._setReadOnlyMode(true);

                var address = this.$address.data('addresses')[this.$address.val()] || null;
                if (address) {
                    var self = this;

                    _.each(address, function(value, name) {
                        if (_.isObject(value)) {
                            value = _.first(_.values(value));
                        }
                        var $field = self.fieldsByName[self.normalizeName(name)] || null;
                        if ($field) {
                            $field.val(value);
                            if ($field.data('select2')) {
                                $field.data('selected-data', value).change();
                            }
                        }
                    });
                }
            } else {
                this._setReadOnlyMode(false);
            }
        },

        _setReadOnlyMode: function(mode) {
            this.$fields.each(function() {
                $(this).prop('readonly', mode).inputWidget('refresh');
            });
        },

        /**
         * Show loading view
         */
        loadingStart: function(e) {
            if (e.updateFields !== undefined && _.contains(e.updateFields, this.options.type + "Address") !== true) {
                return;
            }
            this.loadingMaskView.show();
        },

        /**
         * Hide loading view
         */
        loadingEnd: function() {
            this.loadingMaskView.hide();
        },

        /**
         * Set customer address choices from order related data
         *
         * @param {Object} response
         */
        loadFormChanges: function(response) {
            var address = response[this.options.type + 'Address'] || null;
            if (!address) {
                this.loadingEnd();
                return;
            }

            var $oldAddress = this.$address;
            this.setAddress($($.trim(address.replace(/<!--(.*?)-->/ig, ''))));

            $oldAddress.parent().trigger('content:remove');
            $oldAddress.inputWidget('dispose');
            $oldAddress.replaceWith(this.$address);

            if (this.useDefaultAddress) {
                this.$address.val(this.$address.data('default')).change();
            }

            this.initLayout().done(_.bind(this.loadingEnd, this));
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('subscription:form-changes:trigger', this.loadingStart, this);
            mediator.off('subscription:form-changes:load', this.loadFormChanges, this);

            SubscriptionAddressView.__super__.dispose.call(this);
        }
    });

    return SubscriptionAddressView;
});
