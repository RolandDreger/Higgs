<template>
	<k-field 
		class="k-epub-export-fied" 
		:label="label" 
		:disabled="disabled"
		:required="required"
		:help="help"
	>
		<k-button 
			class="k-epub-export-button" 
			theme="positiv"
			:buttonLabel="buttonLabel"  
			:disabled="disabled"
			:icon="icon"
			:tooltip="tooltip"
			@click="exportEpub"
		>
			<k-button-text>{{ buttonLabel }}</k-button-text>
		</k-button>
	</k-field>
</template>

<script>
export default {
  props: {
		label: String,
		buttonLabel: String,
		help: String,
		tooltip: String,
		disabled: Boolean,
		required: Boolean,
		icon: String,
		endpoints: Object
	},
	data: {
		userHelp: String
	},
	methods: {
		exportEpub(event) {
			
			const pageID = this.$route.params.path;
			if(!pageID) {
				console.error('Page could not found: ' + pageID);
				return false;
			}
			
			this.userHelp = this.help;
			this.help = 'Export processing ...';
			this.disabled = true;
			
			const postObj = { 
				'page': pageID
			};
			
			const apiUrl = this.endpoints.field + '/export/epub';
			
			this.$api.post(apiUrl, postObj)
			.then(resObj => {
				
				const errorArray = resObj['data']['errors'];
				if(errorArray.length === 0) {
					/* Success */
					const epubUrl = resObj['data']['url'];
					const epubFileName = resObj['data']['fileName'];
					this.$store.dispatch('notification/success', 'ePub exported to content folder');
					this.help = `Download: <a href="${epubUrl}" type="application/epub+zip" download="${epubFileName}">ePub</a>`;
				} else {
					/* Error */
					this.$store.dispatch('notification/error', 'Export failed');
					this.help = '';
					for(let err of errorArray) {
						console.error(err);
					}
				}
				
				this.disabled = false;
				
				setTimeout(() => { 
					this.help = this.userHelp; 
				}, 4000);
			})
			.catch(err => {
					console.error(err);
			});
		}
	}
};
</script>

<style lang="scss">
	
	.k-epub-export-button {
		width: 100%;
		text-align: left;
		vertical-align: middle;
		background: #fff;
		box-shadow: var(--box-shadow-item);
	}

	.k-epub-export-button span:first-child {
		width: 38px;
		height: 38px;
		background-color: #2b2b2b;
		line-height: 0;
		color:#fff;
	}

	.k-epub-export-button:focus span:first-child,
	.k-epub-export-button:active span:first-child,
	.k-epub-export-button:hover span:first-child {
		background-color: #4271ae;
	}

	.k-epub-export-button span:last-child {
		margin-left: 0.5rem;
	}
	
</style>