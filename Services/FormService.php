<?php

namespace Modules\Form\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Form\Entities\Form;
use Modules\Form\Entities\FormAssociation;
use Modules\Form\Entities\FormItem;
use Modules\Form\Entities\FormValue;
use Modules\Quiz\App\Enums\QuestionType;
use Modules\Starter\Entities\BaseModel;
use Modules\Starter\Services\BaseService;

class FormService extends BaseService
{
	/**
	 * 获取前端渲染用的表单
	 * 主要做数据结构的整理及表单值的对应
	 * @param $form_id
	 * @param BaseModel|null $service 关联业务
	 * @param Model|null $filler 答题人
	 * @return array
	 */
	public function getRenderForm($form_id, ?Model $service = null, ?Model $filler = null): array
	{
		$form = Form::select(['id', 'name', 'slug', 'type', 'description', 'is_active'])->find($form_id);

		if (!$form) {
			return [null, '表单不存在'];
		}

		$items = FormItem::where('form_id', $form_id)->orderByDesc('sort_order')->get();

		$item_ids = $items->pluck('id');

		if ($filler && $service) {
			$values = FormValue::whereIn('form_item_id', $item_ids)
				->where('service_type', get_class($service))->where('service_id', $service->getKey())
				->where('filler_type', get_class($filler))->where('filler_id', $filler->getKey())->get();

			//转换成 Array 主要是为了调用 BaseModal 的 accessor 将  value 中的文件路径转换为完整的 URL
			$values = $values->keyBy('form_item_id')->toArray();

			$form_items = $items->map(function ($item) use ($values) {
				if (isset($values[$item->id])) {
					return array_merge(['defaultValue' => $values[$item->id]['value']], $item->props);
				} else {
					return $item->props;
				}
			});
		} else {
			$form_items = $items->map(fn($item) => $item->props);
		}

		$form->{'items'} = $form_items;

		return [$form, null];
	}

	/**
	 * 获取前端渲染用的知识竞赛表单
	 * 主要做数据结构的整理及表单值的对应
	 * @param $form_id
	 * @param int $items_count 获取的题目数量，0 表示获取所有题目
	 * @param bool $is_random 是否随机获取题目
	 * @param bool $purify 是否需要过滤答案之类的敏感数据
	 * @param BaseModel|null $service 关联业务
	 * @param Model|null $filler 答题人
	 * @return array
	 */
	public function getRenderFormForQuiz($form_id, int $items_count = 0, bool $is_random = false, bool $purify = true, ?Model $service = null, ?Model $filler = null): array
	{
		$form = Form::select(['id', 'name', 'slug', 'type', 'description', 'is_active'])->find($form_id);

		if (!$form) {
			return [null, '表单不存在'];
		}

		$form_item_query = FormItem::where('form_id', $form_id);

		if ($is_random) {
			$form_item_query = $form_item_query->inRandomOrder();
		} else {
			$form_item_query = $form_item_query->orderByDesc('sort_order');
		}

		if (!$items_count) {
			$items = $form_item_query->get();
		} else {
			$items = $form_item_query->take($items_count)->get();
		}

		$item_ids = $items->pluck('id');

		if ($filler && $service) {
			$values = FormValue::whereIn('form_item_id', $item_ids)
				->where('service_type', get_class($service))->where('service_id', $service->getKey())
				->where('filler_type', get_class($filler))->where('filler_id', $filler->getKey())->get();

			$values = $values->keyBy('form_item_id');

			$form_items = $items->map(function ($item) use ($values) {
				if (isset($values[$item->id])) {
					return array_merge(['defaultValue' => $values[$item->id]->value], $item->props);
				} else {
					return $item->props;
				}
			});
		} else {
			$form_items = $items->map(fn($item) => $item->props);
		}

		// 过滤掉 __ 开头的属性
		if ($purify) {
			$form_items = $form_items->map(function ($form_item) {
				return collect($form_item)->filter(function ($value, $key) {
					return !Str::startsWith($key, '__');
				})->all();
			});
		}

		$form->{'items'} = $form_items;

		return [$form, null];
	}


	/**
	 * 创建表单
	 * @param $name
	 * @param array $props
	 * @param Model|null $association_target
	 * @param array $form_items
	 * @param null $creator_id
	 * @param null $department_id
	 * @return array
	 */
	public function createForm($name, array $props = [], ?Model $association_target = null, array $form_items = [], $creator_id = null, $department_id = null): array
	{

		if ($association_target) {
			$association = FormAssociation::with(['form'])->where('association_type', get_class($association_target))->where('association_id', $association_target->getKey())->first();
			if ($association && $association->form) {
				$form = $association->form;
			}
		}
		if (!isset($form)) {
			$form_slug = Str::random(8);
			while (!land_is_model_unique(['slug' => $form_slug], Form::class, 'slug')) {
				$form_slug = Str::random(8);
			}

			$form = Form::create(array_merge([
				'creator_id' => $creator_id ?? auth()->id(),
				'department_id' => $department_id,
				'slug' => $form_slug,
				'name' => $name
			], $props));

			if ($association_target) {
				FormAssociation::create(['form_id' => $form->id, 'association_type' => get_class($association_target), 'association_id' => $association_target->getKey()]);

				if (array_key_exists('form_id', $association_target->attributesToArray())) {
					$association_target->form_id = $form->id;
					$association_target->save();
				}
			}
		}

		$this->editFromNewbie($form_items, $form);

		return [$form, null];
	}

	/**
	 * 适配 Newbie 表单的编辑
	 * @param array $newbie_form_items
	 * @param Form $form
	 * @return array
	 */
	public function editFromNewbie(array $newbie_form_items, Form $form): array
	{
		$form_items = [];

		$items_size = count($newbie_form_items);

		foreach ($newbie_form_items as $index => $form_item) {
			$form_items[] = [
				'form_id' => $form->id,
				'name' => $form_item['title'] ?? '未命名',
				'slug' => $form_item['key'] ?? Str::random(),
				'is_required' => $form_item['required'] ?? false,
				'props' => $form_item,
				'type' => $form_item['type'],
				'sort_order' => $items_size - $index,
			];
		}

		return $this->editForm($form_items, $form);
	}

	/**
	 * 编辑表单
	 * @param array $form_items
	 * @param Form $form
	 * @return array
	 */
	public function editForm(array $form_items, Form $form): array
	{
		try {

			$original_ids = FormItem::where('form_id', $form->id)->get(['id'])->pluck('id');

			DB::beginTransaction();
			foreach ($form_items as $form_item) {
				$existing_item = FormItem::where('form_id', $form->id)->where('slug', $form_item['slug'])->first();
				$form_item['form_id'] = $form->id;
				if ($existing_item) {
					$existing_item->update($form_item);
					$original_ids = $original_ids->filter(function ($value) use ($existing_item) {
						return $value != $existing_item->id;
					});
				} else {
					FormItem::create($form_item);
				}
			}

			FormItem::whereIn('id', $original_ids)->delete();
			DB::commit();
			return [true, null];
		} catch (\Exception $e) {
			DB::rollBack();
			Log::error("FormItemsEditError:" . $e->getMessage());
			return [false, '表单保存失败'];
		}
	}

	/**
	 * 保存数据
	 * @param array $values [key => value, key1 => value1]
	 * @param Form $form
	 * @param Model $filler
	 * @param Model $service
	 * @return array [[chapter_count => 2, current_chapter => 1]], error_message]
	 */
	public function saveValues(array $values, Form $form, Model $filler, Model $service): array
	{
		$form->loadMissing(['items']);

		$form_items = $form->items->keyBy('slug');

		$sorted_form_items = $form->items->sortByDesc('sort_order')->values();
		$current_chapter = 0;
		$chapter_count = 0;
		$value_keys = array_keys($values);
		foreach ($sorted_form_items as $index => $sorted_form_item) {
			if ($index === 0 || $sorted_form_item->props['break']) {
				$chapter_count += 1;
			}
			if (in_array($sorted_form_item->slug, $value_keys)) {
				$current_chapter = $chapter_count;
			}
		}


		//先检测必填
		//分成两次循环的原因是让每一批次的答案都通过再进行保存

		/**
		 * @var FormItem $form_item
		 */
		foreach ($form_items as $slug => $form_item) {
			if ($form_item->is_required) {
				if (!array_key_exists($slug, $values)
					|| (is_array($values[$slug]) && empty($values[$slug]))
					|| (is_string($values[$slug]) && empty(trim($values[$slug])))) {
					return [$slug, "请完成{$form_item->name}"];
				}
			}
		}

		foreach ($values as $key => $value) {
			$existing_value = FormValue::where('form_id', $form->id)
				->where('filler_type', get_class($filler))->where('filler_id', $filler->getKey())
				->where('service_type', get_class($service))->where('service_id', $service->getKey())
				->where('form_item_id', $form_items[$key]->id)->first();

			if ($existing_value) {
				$existing_value->value = $value;
				$existing_value->save();
				continue;
			}

			FormValue::create([
				'form_id' => $form->id,
				'form_item_id' => $form_items[$key]->id,
				'type' => $form_items[$key]->type,
				'filler_type' => get_class($filler),
				'filler_id' => $filler->getKey(),
				'service_type' => get_class($service),
				'service_id' => $service->getKey(),
				'value' => $value
			]);
		}

		return [compact('chapter_count', 'current_chapter'), null];
	}


	/**
	 * 保存知识竞赛数据(单题模式)
	 * @param array $answer
	 * @param int $form_item_id
	 * @param Model $filler
	 * @param Model $service
	 * @return array
	 */
	public function saveSingleValueFormQuiz(array $answer, int $form_item_id, Model $filler, Model $service): array
	{
		$form_item = FormItem::find($form_item_id);

		$correct_answer = $form_item->props['__quiz_answer'];
		$question_score = $form_item->props['quiz_score'];

		$score = 0;
		$is_correct = true;

		$value = $answer[$form_item->slug] ?? '';

		if ($form_item->type == QuestionType::RADIO) {
			if (Str::startsWith($value, strtoupper($correct_answer))) {
				$score = $question_score;
			} else {
				$is_correct = false;
			}
		} else if ($form_item->type == QuestionType::CHECKBOX) {
			$correct_answer = collect($correct_answer)->map(fn($item) => strtoupper($item));
			$value = collect($value);
			$is_correct = $correct_answer->count() === $value->count();
			if ($is_correct) {
				foreach ($correct_answer as $answer) {
					if (!$value->filter(fn($item) => Str::startsWith($item, $answer))->count()) {
						$is_correct = false;
						break;
					};
				}
			}
			if ($is_correct) {
				$score = $question_score;
			}
		} else if ($form_item->type == QuestionType::INPUT) {
			if (trim($value) === trim($correct_answer)) {
				$score = $question_score;
			} else {
				$is_correct = false;
			}
		}

		FormValue::create([
			'form_id' => $form_item->form_id,
			'form_item_id' => $form_item->id,
			'type' => $form_item->type,
			'filler_type' => get_class($filler),
			'filler_id' => $filler->getKey(),
			'service_type' => get_class($service),
			'service_id' => $service->getKey(),
			'value' => $value
		]);

		return [compact('score', 'is_correct'), null];

	}


	/**
	 * 批量回答知识竞赛数据
	 * @param array $values [key => value, key1 => value1]
	 * @param Form $form
	 * @param Model $filler
	 * @param Model $service
	 * @return array
	 */
	public function saveValuesForQuiz(array $values, Form $form, Model $filler, Model $service): array
	{

		$value_keys = array_keys($values);
		$form_items = FormItem::where('form_id', $form->id)->whereIn('slug', $value_keys)->get()->keyBy('slug');

		$score = 0;

		foreach ($values as $key => $value) {

			$form_item = $form_items[$key];

			/**
			 * 算分
			 * 正确答案只是 A、B、C、D 的字符或者是数组，但 value 的内容为 A.xxxx B.xxxx C.xxxx D.xxxx
			 * 包含了选项的字符，所以判断是否正确时只需要判断是否包含正确的选项字符即可
			 */

			$correct_answer = $form_item->props['__quiz_answer'];
			$question_score = $form_item->props['quiz_score'];


			if ($form_item->type == QuestionType::RADIO) {
				if (Str::startsWith($value, strtoupper($correct_answer))) {
					$score += $question_score;
				}
			} else if ($form_item->type == QuestionType::CHECKBOX) {
				$correct_answer = collect($correct_answer)->map(fn($item) => strtoupper($item));
				$value = collect($value);
				$is_correct = $correct_answer->count() === $value->count();
				if ($is_correct) {
					foreach ($correct_answer as $answer) {
						if (!$value->filter(fn($item) => Str::startsWith($item, $answer))->count()) {
							$is_correct = false;
							break;
						};
					}
				}
				if ($is_correct) {
					$score += $question_score;
				}
			} else if ($form_item->type == QuestionType::INPUT) {
				if (trim($value) === trim($correct_answer)) {
					$score += $question_score;
				}
			}

			FormValue::create([
				'form_id' => $form->id,
				'form_item_id' => $form_item->id,
				'type' => $form_item->type,
				'filler_type' => get_class($filler),
				'filler_id' => $filler->getKey(),
				'service_type' => get_class($service),
				'service_id' => $service->getKey(),
				'value' => $value
			]);

		}

		return [$score, null];
	}

}
