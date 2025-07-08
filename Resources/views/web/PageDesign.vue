<template>
	<NewbieFormDesigner
		:mode="props.mode"
		:title="props.form.name"
		:widgets="state.widgets"
		:form-items="state.designerFormItems"
		@submit="onSubmitForm"
	></NewbieFormDesigner>
</template>

<script setup>
import { inject, reactive } from "vue"
import { useFetch, useProcessStatusSuccess } from "jobsys-newbie/hooks"
import { message } from "ant-design-vue"
import { useGoBack } from "@manager/compositions/util"

const props = defineProps({
	/**
	 * 设计类型，默认为表单设置，可选 quiz: 知识竞赛设计（题目带分值，答案和解析）
	 */
	mode: { type: String, default: null },
	form: {
		type: Object,
		default: () => {},
		required: true,
	},
})

const route = inject("route")

const state = reactive({
	designerFormItems: props.form.items.map((item) => item.props) || [],
})

const onSubmitForm = async (formItems) => {
	const res = await useFetch().post(route("api.manager.form.form-item.edit"), {
		slug: props.form.slug,
		form_items: formItems,
	})
	useProcessStatusSuccess(res, () => {
		message.success("保存成功")
		useGoBack()
	})
}
</script>
<style lang="less">
.newbie-form-designer {
	height: calc(100vh - 150px) !important;
}
</style>
