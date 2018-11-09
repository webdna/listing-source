Garnish.ListingsourceField = Garnish.Base.extend({
	defaults: {
		id: null,
		name: null
	},

	id: null,
	name: null,
	$field: null,
	$sectionSelect: null,
	$typeSelect: null,
	currentType: null,

	$settingsInputs: null,

	init: function(settings) {
		this.setSettings(settings, this.defaults);

		this.id = settings.id || null;
		this.name = settings.name || null;
		this.$field = $('#' + settings.id + '-field');

		this.$sectionSelect = this.$field.find('.select.channel select');
		this.$sectionSelect.trigger('change');
	}
});
