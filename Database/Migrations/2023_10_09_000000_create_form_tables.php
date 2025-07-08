<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		Schema::create('forms', function (Blueprint $table) {
			$table->id();
			$table->integer('department_id')->index()->nullable()->comment('所属部门');
			$table->integer('creator_id')->index()->comment('创建者ID');
			$table->string('name')->index()->comment('表单名称');
			$table->string('slug')->index()->nullable()->comment('标识');
			$table->string('type')->index()->nullable()->default('default')->comment('表单类型，同一个系统中可能有多个表单类型');
			$table->text('description')->nullable()->comment('描述');
			$table->boolean('is_active')->default(true)->comment('是否可用');
			$table->timestamps();
			$table->comment('动态表单表');
		});

		Schema::create('form_widgets', function (Blueprint $table) {
			$table->id();
			$table->integer('category_id')->index()->comment('分组ID');
			$table->string('form_type')->index()->default("default")->comment('表单类型');
			$table->string('name')->index()->comment('组件名称');
			$table->string('icon')->nullable()->comment('组件图标');
			$table->string('type')->nullable()->default('input')->comment('实际类型');
			$table->json('props')->nullable()->comment('预设可配置项');
			$table->timestamps();
			$table->comment('动态表单组件表');
		});

		Schema::create('form_items', function (Blueprint $table) {
			$table->id();
			$table->integer('form_id')->index()->comment('所属表单');
			$table->string('name')->index()->comment('字段名称');
			$table->string('slug')->index()->comment('识别码');
			$table->string('type')->index()->comment('表单类型');
			$table->boolean('is_required')->default(false)->comment('是否必填');
			$table->json('props')->nullable()->comment('表单项配置');
			$table->integer('sort_order')->default(0)->comment('排序');
			$table->string('remark')->nullable()->comment('备注');
			$table->timestamps();
			$table->comment('动态表单表单项表');
		});

		Schema::create('form_values', function (Blueprint $table) {
			$table->id();
			$table->integer('form_id')->index()->comment('所属表单');
			$table->integer('form_item_id')->index()->comment('表单项ID');
			$table->string('filler_type', 50)->index()->nullable()->comment('填写人类型');
			$table->integer('filler_id')->index()->nullable()->comment('填写人ID');
			$table->string('service_type', 50)->index()->nullable()->comment('关联业务类型');
			$table->integer('service_id')->index()->nullable()->comment('关联业务ID');
			$table->string('type')->index()->comment('表单项类型');
			$table->json('value')->nullable()->comment('表单项值');
			$table->timestamps();
			$table->unique(['form_id', 'form_item_id', 'filler_type', 'filler_id', 'service_type', 'service_id'], 'fill_index');
			$table->comment('动态表单表单值表');
		});

		Schema::create('form_associations', function (Blueprint $table) {
			$table->id();
			$table->integer('form_id')->index()->comment('表单ID');
			$table->string('association_type')->index()->comment('关联类型');
			$table->integer('association_id')->index()->comment('关联ID');
			$table->timestamps();
			$table->comment('动态表单关联表');
		});

	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('forms');
		Schema::dropIfExists('form_widgets');
		Schema::dropIfExists('form_items');
		Schema::dropIfExists('form_values');
		Schema::dropIfExists('form_associations');
	}
};
