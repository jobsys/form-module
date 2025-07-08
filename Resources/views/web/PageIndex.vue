<template>
	<a-alert class="mb-4!">
		<template #message> 【表单】仅为生成工具，请结合其它具体业务使用</template>
	</a-alert>
	<NewbieTable class="hover-card" ref="tableRef" :url="route('api.manager.form.items')" :columns="tableColumns()">
		<template #functional>
			<NewbieButton v-if="$auth('api.manager.form.edit')" type="primary" :icon="h(PlusOutlined)" @click="onEdit(false)">新增表单 </NewbieButton>
		</template>
	</NewbieTable>

	<NewbieModal v-model:visible="state.showEditorModal" title="表单编辑">
		<NewbieForm
			ref="edit"
			:fetch-url="state.url"
			:auto-load="!!state.url"
			:submit-url="route('api.manager.form.edit')"
			:submit-disabled="!$auth('api.manager.form.edit')"
			:card-wrapper="false"
			:form="formColumns()"
			:close="closeEditor"
			@success="closeEditor(true)"
		/>
	</NewbieModal>
</template>
<script setup>
import { h, inject, reactive, ref } from "vue"
import { DeleteOutlined, EditOutlined, OrderedListOutlined, PlusOutlined } from "@ant-design/icons-vue"
import { useTableActions } from "jobsys-newbie"
import { useFetch, useModalConfirm, useProcessStatusSuccess } from "jobsys-newbie/hooks"
import { message } from "ant-design-vue"
import { router } from "@inertiajs/vue3"
import { find } from "lodash-es"

const props = defineProps({
	formTypeOptions: { type: Array, default: () => [] },
})

const route = inject("route")
const auth = inject("auth")

const tableRef = ref()

const state = reactive({
	url: "",
	showEditorModal: false,
})

const onEdit = (item) => {
	state.url = item ? route("api.manager.form.item", { id: item.id }) : ""
	state.showEditorModal = true
}

const closeEditor = (isRefresh) => {
	if (isRefresh) {
		tableRef.value.doFetch()
	}
	state.showEditorModal = false
}

const onDesign = (item) => {
	router.visit(route("page.manager.form.design", { slug: item.slug }))
}

const onDelete = (item) => {
	const modal = useModalConfirm(
		`您确认要删除 ${item.name} 吗？`,
		async () => {
			try {
				const res = await useFetch().post(route("api.manager.form.delete"), { id: item.id })
				modal.destroy()
				useProcessStatusSuccess(res, () => {
					message.success("删除成功")
					tableRef.value.doFetch()
				})
			} catch (e) {
				modal.destroy(e)
			}
		},
		true,
	)
}

const formColumns = () => [
	{ title: "表单名称", key: "name", required: true, width: 300 },
	{
		title: "表单类型",
		key: "type",
		width: 300,
		type: "select",
		required: true,
		options: props.formTypeOptions,
		defaultValue: "default",
	},
	{
		title: "描述",
		key: "description",
		type: "textarea",
	},
	{
		title: "是否启用",
		key: "is_active",
		type: "switch",
		defaultValue: true,
	},
]

const tableColumns = () => [
	{ title: "表单名称", dataIndex: "name", width: 200, ellipsis: true, filterable: true },
	{
		title: "表单类型",
		dataIndex: "is_active",
		align: "center",
		width: 100,
		customRender: ({ record }) => find(props.formTypeOptions, { value: record.type }).label,
	},
	{
		title: "是否激活",
		dataIndex: "is_active",
		align: "center",
		width: 100,
		customRender: ({ record }) => useTableActions({ type: "switch", name: ["是", "否"], value: record.is_active }),
	},
	{ title: "创建者", dataIndex: ["creator", "name"], align: "center", width: 100 },
	{ title: "更新时间", dataIndex: "updated_at", width: 160 },
	{ title: "创建时间", dataIndex: "created_at", width: 160 },
	{
		title: "操作",
		width: 260,
		key: "operation",
		align: "center",
		fixed: "right",
		customRender({ record }) {
			const actions = []

			if (auth("api.manager.form.edit")) {
				actions.push({
					name: "编辑",
					props: {
						icon: h(EditOutlined),
						size: "small",
					},
					action() {
						onEdit(record)
					},
				})
			}

			actions.push({
				name: "表单定制",
				props: {
					icon: h(OrderedListOutlined),
					size: "small",
				},
				action() {
					onDesign(record)
				},
			})

			if (auth("api.manager.form.delete")) {
				actions.push({
					name: "删除",
					props: {
						icon: h(DeleteOutlined),
						size: "small",
					},
					action() {
						onDelete(record)
					},
				})
			}

			return useTableActions(actions)
		},
	},
]
</script>
