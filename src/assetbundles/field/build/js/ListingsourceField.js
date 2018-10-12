Garnish.ListingsourceField = Garnish.Base.extend({
	defaults: {
		id: null,
		name: null
	},

	id: null,
	name: null,
	$field: null,

	$typeSelect: null,
	currentType: null,

	$settingsInputs: null,

	init: function(settings) {
		this.setSettings(settings, this.defaults);

		this.id = settings.id || null;
		this.name = settings.name || null;
		this.$field = $('#' + settings.id + '-field');

		this.$typeSelect = this.$field.find('#' + settings.id + '-type');
		this.currentType = this.$typeSelect.val();
		this.addListener(this.$typeSelect, 'change', 'onChangeType');

		this.$settingsInputs = this.$field.find('.listingsource--settings');

		//channels
		this.$channelSelect = this.$field.find('.listingsource--channel .channel select');
		this.currentChannel = this.$channelSelect.val();
		//this.addListener(this.$channelSelect, 'change', 'onChangeChannelType');
		//this.$channelSelect.trigger('change');
	},

	onChangeType: function(e) {
		var $select = $(e.currentTarget);

		this.type = $select.val();
		if (this.type === '') {
			this.$settingsInputs.addClass('hidden');
		} else {
			this.$settingsInputs.removeClass('hidden');
		}
	}

	// onChangeChannelType: function(e) {
	// 	var $select = $(e.currentTarget);
	// 	//console.log($select);
	// 	this.type = $select.val();
	// 	if (this.type === '') {
	// 		this.$settingsInputs.addClass('hidden');
	// 	} else {
	// 		this.$settingsInputs.removeClass('hidden');
	// 	}
	// }
});
