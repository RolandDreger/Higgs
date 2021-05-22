<template>
	<k-field class="k-export-fied" :label="label" :help="help">
		<k-button class="k-export-button" 
			title="ePup Export" 
			:icon="icon" 
			theme="positiv"
			@click="exportEpub"
		><k-button-text>{{ buttonLabel }}</k-button-text></k-button>
	</k-field>
</template>

<script>
export default {
  props: {
		label: String,
		help: String,
		buttonLabel: String,
		icon: String,
		endpoints: Object
	},

	methods: {
		exportEpub(event) {
			var parentPagePath = this.$route.params.path;
			if(!parentPagePath) {
				this.help = 'Page could not found: ' + parentPagePath;
				console.error('Page could not found: ' + parentPagePath);
				return false;
			}
			this.help = "Export processing ...";
			var postObj = { 
				'page': parentPagePath
			};
			this.$api.post(this.endpoints.field + '/export/epub', postObj)
			.then(resObj => {
				const errorArray = resObj['data']['errors'];
				if(errorArray.length === 0) {
					this.help = 'ePub was exported successfully';
				} else {
					this.help = 'Export failed';
					for(let err of errorArray) {
						console.error(err);
					}
				}
				setTimeout(() => { this.help = ''; }, 3000);
			})
			.catch(err => {
					console.log({ 'Error': err });
			});
		}
	}
};
</script>

<style lang="scss">
	
	.k-export-button {
		width: 100%;
		text-align: left;
		vertical-align: middle;
		background: #fff;
		box-shadow: var(--box-shadow-item);
	}

	.k-export-button span:first-child {
		width: 38px;
		height: 38px;
		background-color: #2b2b2b;
		line-height: 0;
		color:#fff;
	}

	.k-export-button:focus span:first-child,
	.k-export-button:active span:first-child,
	.k-export-button:hover span:first-child {
		background-color: #4271ae;
	}

	.k-export-button span:last-child {
		margin-left: 0.5rem;
	}
	
</style>