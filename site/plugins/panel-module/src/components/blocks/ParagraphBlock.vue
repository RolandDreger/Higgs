<template>
	<div class="k-block-type-paragraph">
		<k-writer
			ref="textbox"
			class="k-block-type-paragraph-text"
			:placeholder="paragraphTextField.placeholder || 'Text ...'"
			:inline="true"
			:marks="paragraphTextField.marks"
			:value="content.paragraphtext"
			@input="$emit('update', { paragraphtext: $event })"
		></k-writer>
	</div>
	
</template>

<script>
export default {
	name: 'Paragraph',
	computed: {
		paragraphTextField() {
			return this.field("paragraphtext");
		}
	}
}
</script>

<style>

</style>