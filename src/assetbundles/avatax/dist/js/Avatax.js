/**
 * Avatax plugin for Craft CMS
 *
 * Avatax JS
 *
 * @author    Surprise Highway
 * @copyright Copyright (c) 2019 Surprise Highway
 * @link      http://surprisehighway.com
 * @package   Avatax
 * @since     2.0.0
 */

var Avatax = window.Avatax || {};

Avatax.settings = {

	init: function() {
		$('#settings-test-connection-btn').on('click', this.connectionTest);
	},

	connectionTest: function(e) {
		var message;
		var data = $('form#main-form').serialize();
		var self = Avatax.settings;

		self.showLoading();

		Craft.postActionRequest('avatax/utility/connection-test', data, $.proxy(function(response, textStatus) {
            
            console.log(response);

            if (textStatus == 'success') {
                if (response.authenticated) {
                	message = 'Configuration validated successfully.';
                } else {
                    message = 'Could not connect with the current configuration.';
                }
            } else {
            	message = 'The request to avatax.com failed.';
            }

            self.showModal(message);
            self.hideLoading();
        }, this));
	},

	showModal: function(message) {
		var modalHtml = 
			'<div class="modal fitted settings-modal-message">' +
				'<div class="header"><h1>Test Connection</h1></div>' +
				'<div class="body">'+message+'</div>' +
				'<div class="footer">' +
					'<div class="buttons right">' +
						'<input type="button" class="btn modal-cancel" value="Done"/>' +
					'</div>' +
				'</div>' +
			'</div>';

		var $modal = $(modalHtml).appendTo(Garnish.$bod);
		$modal['modal'] = new Garnish.Modal($modal);

		$modal.find('.modal-cancel').on('click', function() {
			$modal['modal'].hide();
		});
	},

	showLoading: function() {
		$('#settings-test-connection-btn').addClass('disabled');
		$('#settings-test-connection-spinner').removeClass('hidden');
	},

	hideLoading: function() {
		$('#settings-test-connection-btn').removeClass('disabled');
        $('#settings-test-connection-spinner').addClass('hidden');
	}
};

$(function() {

	Avatax.settings.init();

});
