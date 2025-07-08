<?php

namespace Modules\Form\Http\Controllers;

use App\Http\Controllers\BaseManagerController;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Modules\Form\Entities\Form;
use Modules\Form\Services\FormService;
use Modules\Starter\Emnus\State;

class FormController extends BaseManagerController
{

	public function pageIndex()
	{
		$form_type_options = [
			['label' => '默认表单', 'value' => 'default']
		];
		return Inertia::render('PageIndex@Form', [
			'formTypeOptions' => $form_type_options
		]);
	}

	public function pageDesign($slug)
	{
		$form = Form::with(['items'])->where('slug', $slug)->first();

		if ($form) {
			$items = $form->items;
			$form->unsetRelation('items');
			$items = $items->sortByDesc('sort_order')->values()->toArray();
			$form->{'items'} = $items;
		}

		return Inertia::render('PageDesign@Form', ['form' => $form, 'mode' => request('mode')]);
	}

	public function edit()
	{
		[$input, $error] = land_form_validate(
			request()->only(['id', 'department_id', 'creator_id', 'name', 'type', 'description']),
			[
				'name' => 'bail|required|string',
			],
			[
				'name' => '表单名称',
			],
		);

		if ($error) {
			return $this->message($error);
		}

		if (empty($input['id'])) {
			$input['slug'] = Str::random(8);
			while (!land_is_model_unique($input, Form::class, 'slug')) {
				$input['slug'] = Str::random(8);
			}

			$input['creator_id'] = auth()->id();
		}

		$result = Form::updateOrCreate(['id' => $input['id'] ?? 0], $input);
		return $this->json(null, $result ? State::SUCCESS : State::FAIL);
	}

	public function items()
	{
		$pagination = Form::with(['creator:id,name'])->filterable()->paginate();
		return $this->json($pagination);
	}

	public function item($id)
	{
		$item = Form::find($id);
		if (!$item) {
			return $this->message('表单不存在');
		}

		log_access('查看表单详情', $item);

		return $this->json($item);
	}

	public function delete()
	{
		$id = request()->input('id');

		$item = Form::find($id);

		if (!$item) {
			return $this->message('表单信息不存在');
		}

		$item->delete();
		return $this->json();
	}

	public function formItemEdit(FormService $formService)
	{
		[$input, $error] = land_form_validate(
			request()->only(['slug', 'form_items']),
			[
				'slug' => 'bail|required|string',
				'form_items' => 'bail|required|array',
			],
			[
				'slug' => '表单',
				'form_items' => '表单内容',
			],
		);

		if ($error) {
			return $this->message($error);
		}


		$form = Form::where('slug', $input['slug'])->first();

		[, $error] = $formService->editFromNewbie($input['form_items'], $form);

		if ($error) {
			return $this->message($error);
		}

		return $this->json();

	}
}
