<template>
	<k-field
		class="k-epub-file-name-field"	
		:label="label"
		:help="help"
		:disabled="disabled"
		:required="required"
		:when="when"
	>
		<k-input 
			type="text"
			theme="field"
			name="textfield"
			:before="before"
			:after="after"
			:placeholder="placeholder"
			:icon="icon"
			:value="slugValue"
			@input="onInput"
		>
	</k-field>
</template>

<script>
export default {
  props: {
		label: String,
		help: String,
		disabled: Boolean,
		required: Boolean,
		before: String,
		after: String,
		placeholder: String,
		icon: String,
		when: String,
		value: String
	},
	computed: {
		slugValue() {
			const lowerCaseValue = this.value.toLowerCase();
			return this.$helper.slug(lowerCaseValue, [
				{ 'ß': 'sz' },
				{ 'ä': 'ae' },
				{ 'ö': 'oe' },
				{ 'ü': 'ue' }
			]);
		}
	},
	methods: {
		onInput(value) {
			this.$emit("input", value);
		}
	}
};

</script>

<style lang="scss">
	
</style>