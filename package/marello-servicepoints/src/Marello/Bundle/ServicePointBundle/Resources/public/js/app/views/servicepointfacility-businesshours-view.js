define(function(require) {
    'use strict';

    var ServicePointFacilityBusinessHoursView,
        $ = require('jquery'),
        BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export marelloservicepoint/js/app/views/servicepointfacility-businesshours-view
     * @extends oroui.app.views.base.View
     * @class marelloservicepoint.app.views.ServicePointFacilityBusinessHoursView
     */
    ServicePointFacilityBusinessHoursView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {},
        /**
         * @property {jQuery}
         */
        $form: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this.initLayout().done(_.bind(this.handleLayoutInit, this));
            this.delegate('click', '.marello-add-line-item', this.addRow);
        },

        /**
         * Doing something after loading child components
         */
        handleLayoutInit: function() {
            this.$form = this.$el.closest('form');
            this.$el.find('.marello-add-line-item').mousedown(function(e) {
                $(this).click();
            });
        },

        /**
         * handle index and html for the collection container
         * @param $listContainer
         * @returns {{nextIndex: *, nextItemHtml: *}}
         */
        getCollectionInfo: function($listContainer) {
            var index = $listContainer.data('last-index') || $listContainer.children().length;

            var prototypeName = $listContainer.attr('data-prototype-name') || '__name__';
            var html = $listContainer.attr('data-prototype').replace(new RegExp(prototypeName, 'g'), index);
            return {
                nextIndex: index,
                nextItemHtml: html
            };
        },

        /**
         * handle add button
         */
        addRow: function() {
            var _self = this.$el.find('.marello-add-line-item');
            var containerSelector = $(_self).data('container') || '.collection-fields-list';
            var $listContainer = this.$el.find('.row-oro').find(containerSelector).first();
            var collectionInfo = this.getCollectionInfo($listContainer);
            $listContainer.append(collectionInfo.nextItemHtml)
                .trigger('content:changed')
                .data('last-index', collectionInfo.nextIndex + 1);

            $listContainer.find('input.position-input').each(function(i, el) {
                $(el).val(i);
            });
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            ServicePointFacilityBusinessHoursView.__super__.dispose.call(this);
        }
    });

    return ServicePointFacilityBusinessHoursView;
});
