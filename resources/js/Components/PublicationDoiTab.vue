<template>
	<div class="publicationDoiTab">
		<p v-if="!items.length">{{ initData.i18n.noItems }}</p>
		<pkp-table v-else :columns="columns" :rows="items">
			<template #default="{row}">
				<pkp-table-cell :column="columns[0]" :row="row" />
				<pkp-table-cell :column="columns[1]" :row="row" />
				<pkp-table-cell :column="columns[2]" :row="row">
					<input
						v-if="row.doiUrl"
						class="pkpFormField__input publicationDoiTab__doi"
						type="text"
						readonly
						:value="row.doiUrl"
						:size="row.doiUrl.length"
						:aria-label="initData.i18n.doi + ': ' + row.label"
						@focus="$event.target.select()"
						@click="$event.target.select()"
					/>
					<span v-else class="publicationDoiTab__missing">
						{{ initData.i18n.notAssigned }}
					</span>
				</pkp-table-cell>
			</template>
		</pkp-table>
	</div>
</template>

<script>
/**
 * Lists the DOIs of the working publication and its related objects.
 * Read-only: everything comes from the publication object of the workflow page.
 */
export default {
	props: {
		// enabledTypes and i18n, set by PublicationDoiPlugin::handleTemplateDisplay()
		initData: {type: Object, required: true},
		// workingPublication of the workflow page
		publication: {type: Object, required: true},
	},
	computed: {
		columns() {
			const i18n = this.initData.i18n;
			return [
				{name: 'type', label: i18n.type, value: 'typeLabel'},
				{name: 'label', label: i18n.item, value: 'label'},
				{name: 'doi', label: i18n.doi, value: 'doiUrl'},
			];
		},
		items() {
			const p = this.publication;
			const items = [];

			if (this.isEnabled('publication')) {
				this.addItem(
					items,
					'publication',
					p.id,
					this.loc(p.fullTitle) || this.loc(p.title),
					p.doiObject
				);
			}

			// OJS, OPS
			if (this.isEnabled('representation')) {
				(p.galleys || []).forEach((galley) => {
					const label =
						galley.label + (galley.locale ? ` (${galley.locale})` : '');
					this.addItem(
						items,
						'representation',
						galley.id,
						label,
						galley.doiObject
					);
				});
			}

			// OMP: core only gives DOIs to chapters that have a landing page
			if (this.isEnabled('chapter')) {
				(p.chapters || [])
					.filter((chapter) => chapter.isPageEnabled)
					.forEach((chapter) => {
						this.addItem(
							items,
							'chapter',
							chapter.id,
							this.loc(chapter.title),
							chapter.doiObject
						);
					});
			}
			(p.publicationFormats || []).forEach((format) => {
				if (this.isEnabled('representation')) {
					this.addItem(
						items,
						'representation',
						format.id,
						this.loc(format.name),
						format.doiObject
					);
				}
				if (this.isEnabled('file')) {
					(format.submissionFiles || []).forEach((file) => {
						const label = `${this.loc(format.name)} / ${this.loc(file.name)}`;
						this.addItem(items, 'file', file.id, label, file.doiObject);
					});
				}
			});

			return items;
		},
	},
	methods: {
		isEnabled(type) {
			return this.initData.enabledTypes.includes(type);
		},
		loc(value) {
			if (!value) {
				return '';
			}
			return typeof value === 'object' ? this.localize(value) : value;
		},
		addItem(items, type, id, label, doiObject) {
			items.push({
				id: `${type}-${id}`,
				typeLabel: this.initData.i18n.types[type],
				label,
				doiUrl: doiObject && doiObject.doi ? doiObject.resolvingUrl : '',
			});
		},
	},
};
</script>

<style scoped>
.publicationDoiTab {
	/* Publication tab panels have no top padding, the forms in other tabs bring their own */
	padding-top: 2rem;
}

.publicationDoiTab__doi {
	width: auto;
	max-width: 100%;
	box-sizing: border-box;
	font-family: monospace;
}

.publicationDoiTab__missing {
	font-style: italic;
	color: #757575;
}
</style>
