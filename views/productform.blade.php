@extends('layout.default')

@if($mode == 'edit')
@section('title', $__t('Edit product'))
@else
@section('title', $__t('Create product'))
@endif

@section('viewJsName', 'productform')

@push('pageScripts')
<script src="{{ $U('/node_modules/TagManager/tagmanager.js?v=', true) }}{{ $version }}"></script>
<script src="{{ $U('/node_modules/datatables.net-rowgroup/js/dataTables.rowGroup.min.js?v=', true) }}{{ $version }}"></script>
<script src="{{ $U('/node_modules/datatables.net-rowgroup-bs4/js/rowGroup.bootstrap4.min.js?v=', true) }}{{ $version }}"></script>
@endpush

@push('pageStyles')
<link href="{{ $U('/node_modules/TagManager/tagmanager.css?v=', true) }}{{ $version }}"
	rel="stylesheet">
<link href="{{ $U('/node_modules/datatables.net-rowgroup-bs4/css/rowGroup.bootstrap4.min.css?v=', true) }}{{ $version }}"
	rel="stylesheet">
@endpush

@section('content')
<div class="row">
	<div class="col">
		<h2 class="title">@yield('title')</h2>
	</div>
</div>

<hr class="my-2">

<div class="row">
	<div class="col-lg-6 col-xs-12">
		<script>
			Grocy.EditMode = '{{ $mode }}';
		</script>

		@if($mode == 'edit')
		<script>
			Grocy.EditObjectId = {{ $product->id }};
		</script>

		@if(!empty($product->picture_file_name))
		<script>
			Grocy.ProductPictureFileName = '{{ $product->picture_file_name }}';
		</script>
		@endif
		@endif

		<form id="product-form"
			novalidate>

			<div class="form-group">
				<label for="name">{{ $__t('Name') }}</label>
				<input type="text"
					class="form-control"
					required
					id="name"
					name="name"
					value="@if($mode == 'edit'){{ $product->name}}@endif">
				<div class="invalid-feedback">{{ $__t('A name is required') }}</div>
			</div>

			<div class="form-group">
				<div class="custom-control custom-checkbox">
					<input type="hidden"
						name="active"
						value="1">
					<input @if($mode=='create'
						)
						checked
						@elseif($mode=='edit'
						&&
						$product->active == 1) checked @endif class="form-check-input custom-control-input" type="checkbox" id="active" name="active" value="1">
					<label class="form-check-label custom-control-label"
						for="active">{{ $__t('Active') }}</label>
				</div>
			</div>

			@php $prefillById = ''; if($mode=='edit') { $prefillById = $product->parent_product_id; } @endphp
			@php
			$hint = '';
			if ($isSubProductOfOthers)
			{
			$hint = $__t('Not possible because this product is already used as a parent product in another product');
			}
			@endphp
			@include('components.productpicker', array(
			'products' => $products,
			'prefillById' => $prefillById,
			'disallowAllProductWorkflows' => true,
			'isRequired' => false,
			'label' => 'Parent product',
			'disabled' => $isSubProductOfOthers,
			'hint' => $hint
			))
			@php $hint = ''; @endphp

			<div class="form-group">
				<label for="description">{{ $__t('Description') }}</label>
				<textarea class="form-control wysiwyg-editor"
					id="description"
					name="description">@if($mode == 'edit'){{ $product->description }}@endif</textarea>
			</div>

			@if(GROCY_FEATURE_FLAG_STOCK_LOCATION_TRACKING)
			<div class="form-group">
				<label for="location_id">{{ $__t('Default location') }}</label>
				<select required
					class="form-control"
					id="location_id"
					name="location_id">
					<option></option>
					@foreach($locations as $location)
					<option @if($mode=='edit'
						&&
						$location->id == $product->location_id) selected="selected" @endif value="{{ $location->id }}">{{ $location->name }}</option>
					@endforeach
				</select>
				<div class="invalid-feedback">{{ $__t('A location is required') }}</div>
			</div>
			@else
			<input type="hidden"
				name="location_id"
				id="location_id"
				value="1">
			@endif

			@php $prefillById = ''; if($mode=='edit') { $prefillById = $product->shopping_location_id; } @endphp
			@if(GROCY_FEATURE_FLAG_STOCK_PRICE_TRACKING)
			@include('components.shoppinglocationpicker', array(
			'label' => 'Default store',
			'prefillById' => $prefillById,
			'shoppinglocations' => $shoppinglocations
			))
			@else
			<input type="hidden"
				name="shopping_location_id"
				id="shopping_location_id"
				value="1">
			@endif

			@php if($mode == 'edit') { $value = $product->min_stock_amount; } else { $value = 0; } @endphp
			@include('components.numberpicker', array(
			'id' => 'min_stock_amount',
			'label' => 'Minimum stock amount',
			'min' => '0.' . str_repeat('0', $userSettings['stock_decimal_places_amounts'] - 1) . '1',
			'decimals' => $userSettings['stock_decimal_places_amounts'],
			'value' => $value,
			'invalidFeedback' => $__t('The amount cannot be lower than %s', '0'),
			'additionalGroupCssClasses' => 'mb-1'
			))

			<div class="form-group">
				<div class="custom-control custom-checkbox">
					<input type="hidden"
						name="cumulate_min_stock_amount_of_sub_products"
						value="0">
					<input @if($mode=='edit'
						&&
						$product->cumulate_min_stock_amount_of_sub_products == 1) checked @endif class="form-check-input custom-control-input" type="checkbox" id="cumulate_min_stock_amount_of_sub_products" name="cumulate_min_stock_amount_of_sub_products" value="1">
					<label class="form-check-label custom-control-label"
						for="cumulate_min_stock_amount_of_sub_products">{{ $__t('Accumulate sub products min. stock amount') }}
						&nbsp;<i class="fas fa-question-circle"
							data-toggle="tooltip"
							title="{{ $__t('If enabled, the min. stock amount of sub products will be accumulated into this product, means the sub product will never be "missing", only this product') }}"></i></span>
					</label>
				</div>
			</div>

			@if(GROCY_FEATURE_FLAG_STOCK_BEST_BEFORE_DATE_TRACKING)
			@php if($mode == 'edit') { $value = $product->default_best_before_days; } else { $value = 0; } @endphp
			@include('components.numberpicker', array(
			'id' => 'default_best_before_days',
			'label' => 'Default best before days',
			'min' => -1,
			'value' => $value,
			'invalidFeedback' => $__t('The amount cannot be lower than %s', '-1'),
			'hint' => $__t('For purchases this amount of days will be added to today for the best before date suggestion') . ' (' . $__t('-1 means that this product never expires') . ')'
			))

			@if(GROCY_FEATURE_FLAG_STOCK_PRODUCT_OPENED_TRACKING)
			@php if($mode == 'edit') { $value = $product->default_best_before_days_after_open; } else { $value = 0; } @endphp
			@include('components.numberpicker', array(
			'id' => 'default_best_before_days_after_open',
			'label' => 'Default best before days after opened',
			'min' => 0,
			'value' => $value,
			'invalidFeedback' => $__t('The amount cannot be lower than %s', '-1'),
			'hint' => $__t('When this product was marked as opened, the best before date will be replaced by today + this amount of days (a value of 0 disables this)')
			))
			@endif
			@endif

			<div class="form-group">
				<label for="product_group_id">{{ $__t('Product group') }}</label>
				<select class="form-control"
					id="product_group_id"
					name="product_group_id">
					<option></option>
					@foreach($productgroups as $productgroup)
					<option @if($mode=='edit'
						&&
						$productgroup->id == $product->product_group_id) selected="selected" @endif value="{{ $productgroup->id }}">{{ $productgroup->name }}</option>
					@endforeach
				</select>
			</div>

			<div class="form-group">
				<label for="qu_id_purchase">{{ $__t('Quantity unit purchase') }}</label>
				<select required
					class="form-control input-group-qu"
					id="qu_id_purchase"
					name="qu_id_purchase">
					<option></option>
					@foreach($quantityunits as $quantityunit)
					<option @if($mode=='edit'
						&&
						$quantityunit->id == $product->qu_id_purchase) selected="selected" @endif value="{{ $quantityunit->id }}">{{ $quantityunit->name }}</option>
					@endforeach
				</select>
				<div class="invalid-feedback">{{ $__t('A quantity unit is required') }}</div>
			</div>

			<div class="form-group">
				<label for="qu_id_stock">{{ $__t('Quantity unit stock') }}</label>
				<i class="fas fa-question-circle"
					data-toggle="tooltip"
					title="{{ $__t('Quantity unit stock cannot be changed after first purchase') }}"></i>
				<select required
					class="form-control input-group-qu"
					id="qu_id_stock"
					name="qu_id_stock"
					@if($mode=='edit'
					)
					disabled
					@endif>
					<option></option>
					@foreach($quantityunits as $quantityunit)
					<option @if($mode=='edit'
						&&
						$quantityunit->id == $product->qu_id_stock) selected="selected" @endif value="{{ $quantityunit->id }}" data-plural-form="{{ $quantityunit->name_plural }}">{{ $quantityunit->name }}</option>
					@endforeach
				</select>
				<div class="invalid-feedback">{{ $__t('A quantity unit is required') }}</div>
			</div>

			@php if($mode == 'edit') { $value = $product->qu_factor_purchase_to_stock; } else { $value = 1; } @endphp
			@include('components.numberpicker', array(
			'id' => 'qu_factor_purchase_to_stock',
			'label' => 'Factor purchase to stock quantity unit',
			'min' => '0.' . str_repeat('0', $userSettings['stock_decimal_places_amounts'] - 1) . '1',
			'decimals' => $userSettings['stock_decimal_places_amounts'],
			'value' => $value,
			'invalidFeedback' => $__t('The amount cannot be lower than %s', '1'),
			'additionalCssClasses' => 'input-group-qu',
			'additionalHtmlElements' => '<p id="qu-conversion-info"
				class="form-text text-info d-none"></p>'
			))

			<div class="form-group">
				<div class="custom-control custom-checkbox">
					<input type="hidden"
						name="allow_partial_units_in_stock"
						value="0">
					<input @if($mode=='edit'
						&&
						$product->allow_partial_units_in_stock == 1) checked @endif class="form-check-input custom-control-input" type="checkbox" id="allow_partial_units_in_stock" name="allow_partial_units_in_stock" value="1">
					<label class="form-check-label custom-control-label"
						for="allow_partial_units_in_stock">{{ $__t('Allow partial units in stock') }}</label>
				</div>
			</div>

			<div class="form-group mb-1">
				<div class="custom-control custom-checkbox">
					<input type="hidden"
						name="enable_tare_weight_handling"
						value="0">
					<input @if($mode=='edit'
						&&
						$product->enable_tare_weight_handling == 1) checked @endif class="form-check-input custom-control-input" type="checkbox" id="enable_tare_weight_handling" name="enable_tare_weight_handling" value="1">
					<label class="form-check-label custom-control-label"
						for="enable_tare_weight_handling">{{ $__t('Enable tare weight handling') }}
						&nbsp;<i class="fas fa-question-circle"
							data-toggle="tooltip"
							title="{{ $__t('This is useful e.g. for flour in jars - on purchase/consume/inventory you always weigh the whole jar, the amount to be posted is then automatically calculated based on what is in stock and the tare weight defined below') }}"></i>
					</label>
				</div>
			</div>

			@php if($mode == 'edit') { $value = $product->tare_weight; } else { $value = 0; } @endphp
			@php if(($mode == 'edit' && $product->enable_tare_weight_handling == 0) || $mode == 'create') { $additionalAttributes = 'disabled'; } else { $additionalAttributes = ''; } @endphp
			@include('components.numberpicker', array(
			'id' => 'tare_weight',
			'label' => 'Tare weight',
			'min' => '0.' . str_repeat('0', $userSettings['stock_decimal_places_amounts'] - 1) . '1',
			'decimals' => $userSettings['stock_decimal_places_amounts'],
			'value' => $value,
			'invalidFeedback' => $__t('This cannot be lower than %s', '0'),
			'additionalAttributes' => $additionalAttributes,
			'contextInfoId' => 'tare_weight_qu_info'
			))
			@php $additionalAttributes = '' @endphp

			@if(GROCY_FEATURE_FLAG_RECIPES)
			<div class="form-group">
				<div class="custom-control custom-checkbox">
					<input type="hidden"
						name="not_check_stock_fulfillment_for_recipes"
						value="0">
					<input @if($mode=='edit'
						&&
						$product->not_check_stock_fulfillment_for_recipes == 1) checked @endif class="form-check-input custom-control-input" type="checkbox" id="not_check_stock_fulfillment_for_recipes" name="not_check_stock_fulfillment_for_recipes" value="1">
					<label class="form-check-label custom-control-label"
						for="not_check_stock_fulfillment_for_recipes">{{ $__t('Disable stock fulfillment checking for this ingredient') }}
						&nbsp;<i class="fas fa-question-circle"
							data-toggle="tooltip"
							title="{{ $__t('This will be used as the default setting when adding this product as a recipe ingredient') }}"></i>
					</label>
				</div>
			</div>

			@php if($mode == 'edit') { $value = $product->calories; } else { $value = 0; } @endphp
			@include('components.numberpicker', array(
			'id' => 'calories',
			'label' => 'Energy (kcal)',
			'min' => '0.' . str_repeat('0', $userSettings['stock_decimal_places_amounts']),
			'decimals' => $userSettings['stock_decimal_places_amounts'],
			'value' => $value,
			'invalidFeedback' => $__t('The amount cannot be lower than %s', '0'),
			'hint' => $__t('Per stock quantity unit'),
			'isRequired' => false
			))
			@endif

			@if(GROCY_FEATURE_FLAG_STOCK_PRODUCT_FREEZING)
			@php if($mode == 'edit') { $value = $product->default_best_before_days_after_freezing; } else { $value = 0; } @endphp
			@include('components.numberpicker', array(
			'id' => 'default_best_before_days_after_freezing',
			'label' => 'Default best before days after freezing',
			'min' => -1,
			'value' => $value,
			'invalidFeedback' => $__t('The amount cannot be lower than %s', '0'),
			'hint' => $__t('On moving this product to a freezer location (so when freezing it), the best before date will be replaced by today + this amount of days')
			))

			@php if($mode == 'edit') { $value = $product->default_best_before_days_after_thawing; } else { $value = 0; } @endphp
			@include('components.numberpicker', array(
			'id' => 'default_best_before_days_after_thawing',
			'label' => 'Default best before days after thawing',
			'min' => -1,
			'value' => $value,
			'invalidFeedback' => $__t('The amount cannot be lower than %s', '0'),
			'hint' => $__t('On moving this product from a freezer location (so when thawing it), the best before date will be replaced by today + this amount of days')
			))
			@else
			<input type="hidden"
				name="default_best_before_days_after_freezing"
				value="0">
			<input type="hidden"
				name="default_best_before_days_after_thawing"
				value="0">
			@endif

			@include('components.userfieldsform', array(
			'userfields' => $userfields,
			'entity' => 'products'
			))

			<small class="my-2 form-text text-muted @if($mode == 'edit') d-none @endif">{{ $__t('Save & continue to add quantity unit conversions & barcodes') }}</small>

			<button id="save-product-button"
				class="save-product-button btn btn-success mb-2"
				data-location="continue">{{ $__t('Save & continue') }}</button>
			<button class="save-product-button btn btn-info mb-2"
				data-location="return">{{ $__t('Save & return to products') }}</button>
		</form>

	</div>

	<div class="col-lg-6 col-xs-12 @if($mode == 'create') d-none @endif">
		<div class="row">
			<div class="col">
				<div class="title-related-links">
					<h4>
						{{ $__t('Barcodes') }}
					</h4>
					<button class="btn btn-outline-dark d-md-none mt-2 float-right order-1 order-md-3"
						type="button"
						data-toggle="collapse"
						data-target="#related-links">
						<i class="fas fa-ellipsis-v"></i>
					</button>
					<div class="related-links collapse d-md-flex order-2 width-xs-sm-100"
						id="related-links">
						<a class="btn btn-outline-primary btn-sm m-1 mt-md-0 mb-md-0 float-right show-as-dialog-link"
							href="{{ $U('/productbarcodes/new?embedded&product=' . $product->id ) }}">
							{{ $__t('Add') }}
						</a>
					</div>
				</div>

				<h5 id="barcode-headline-info"
					class="text-muted font-italic"></h5>

				<table id="barcode-table"
					class="table table-sm table-striped nowrap w-100">
					<thead>
						<tr>
							<th class="border-right"></th>
							<th>{{ $__t('Barcode') }}</th>
							@if(GROCY_FEATURE_FLAG_STOCK_PRICE_TRACKING)
							<th>{{ $__t('Store') }}</th>
							@endif
							<th>{{ $__t('Quantity unit') }}</th>
							<th>{{ $__t('Amount') }}</th>
						</tr>
					</thead>
					<tbody class="d-none">
						@if($mode == "edit")
						@foreach($barcodes as $barcode)
						@if($barcode->product_id == $product->id || $barcode->product_id == null)
						<tr>
							<td class="fit-content border-right">
								<a class="btn btn-sm btn-info show-as-dialog-link @if($barcode->product_id == null) disabled @endif"
									href="{{ $U('/productbarcodes/' . $barcode->id . '?embedded&product=' . $product->id ) }}">
									<i class="fas fa-edit"></i>
								</a>
								<a class="btn btn-sm btn-danger barcode-delete-button @if($barcode->product_id == null) disabled @endif"
									href="#"
									data-barcode-id="{{ $barcode->id }}"
									data-barcode="{{ $barcode->barcode }}"
									data-product-barcode="{{ $product->barcode }}"
									data-product-id="{{ $product->id }}">
									<i class="fas fa-trash"></i>
								</a>
							</td>
							<td>
								{{ $barcode->barcode }}
							</td>
							@if(GROCY_FEATURE_FLAG_STOCK_PRICE_TRACKING)
							<td id="barcode-shopping-location">
								@if (FindObjectInArrayByPropertyValue($shoppinglocations, 'id', $barcode->shopping_location_id) !== null)
								{{ FindObjectInArrayByPropertyValue($shoppinglocations, 'id', $barcode->shopping_location_id)->name }}
								@endif
							</td>
							@endif
							<td>
								@if(!empty($barcode->qu_id))
								<span class="locale-number locale-number-quantity-amount">{{ FindObjectInArrayByPropertyValue($quantityunits, 'id', $barcode->qu_id)->name }}</span>
								@endif
							</td>
							<td>
								@if(!empty($barcode->amount))
								{{ $barcode->amount }}
								@endif
							</td>
						</tr>
						@endif
						@endforeach
						@endif
					</tbody>
				</table>
			</div>
		</div>

		<div class="row mt-5">
			<div class="col">
				<div class="title-related-links">
					<h4>
						{{ $__t('QU conversions') }}
					</h4>
					<button class="btn btn-outline-dark d-md-none mt-2 float-right order-1 order-md-3"
						type="button"
						data-toggle="collapse"
						data-target="#related-links">
						<i class="fas fa-ellipsis-v"></i>
					</button>
					<div class="related-links collapse d-md-flex order-2 width-xs-sm-100"
						id="related-links">
						<a class="btn btn-outline-primary btn-sm m-1 mt-md-0 mb-md-0 float-right show-as-dialog-link"
							href="{{ $U('/quantityunitconversion/new?embedded&product=' . $product->id ) }}">
							{{ $__t('Add') }}
						</a>
					</div>
				</div>

				<h5 id="qu-conversion-headline-info"
					class="text-muted font-italic"></h5>

				<table id="qu-conversions-table"
					class="table table-sm table-striped nowrap w-100">
					<thead>
						<tr>
							<th class="border-right"></th>
							<th>{{ $__t('Quantity unit from') }}</th>
							<th>{{ $__t('Quantity unit to') }}</th>
							<th>{{ $__t('Factor') }}</th>
							<th class="d-none">Hidden group</th>
							<th></th>
						</tr>
					</thead>
					<tbody class="d-none">
						@if($mode == "edit")
						@foreach($quConversions as $quConversion)
						@if($quConversion->product_id == $product->id || $quConversion->product_id == null && ($quConversion->product_id != null || $quConversion->from_qu_id == $product->qu_id_purchase || $quConversion->from_qu_id == $product->qu_id_stock || $quConversion->to_qu_id == $product->qu_id_purchase || $quConversion->to_qu_id == $product->qu_id_stock))
						<tr>
							<td class="fit-content border-right">
								<a class="btn btn-sm btn-info show-as-dialog-link @if($quConversion->product_id == null) disabled @endif"
									href="{{ $U('/quantityunitconversion/' . $quConversion->id . '?embedded&product=' . $product->id ) }}">
									<i class="fas fa-edit"></i>
								</a>
								<a class="btn btn-sm btn-danger qu-conversion-delete-button @if($quConversion->product_id == null) disabled @endif"
									href="#"
									data-qu-conversion-id="{{ $quConversion->id }}">
									<i class="fas fa-trash"></i>
								</a>
							</td>
							<td>
								{{ FindObjectInArrayByPropertyValue($quantityunits, 'id', $quConversion->from_qu_id)->name }}
							</td>
							<td>
								{{ FindObjectInArrayByPropertyValue($quantityunits, 'id', $quConversion->to_qu_id)->name }}
							</td>
							<td>
								<span class="locale-number locale-number-quantity-amount">{{ $quConversion->factor }}</span>
							</td>
							<td class="d-none">
								@if($quConversion->product_id != null)
								{{ $__t('Product overrides') }}
								@else
								{{ $__t('Default conversions') }}
								@endif
							</td>
							<td class="font-italic">
								{{ $__t('This means 1 %1$s is the same as %2$s %3$s', FindObjectInArrayByPropertyValue($quantityunits, 'id', $quConversion->from_qu_id)->name, $quConversion->factor, FindObjectInArrayByPropertyValue($quantityunits, 'id', $quConversion->to_qu_id)->name) }}
							</td>
						</tr>
						@endif
						@endforeach
						@endif
					</tbody>
				</table>
			</div>
		</div>

		<div class="row mt-5">
			<div class="col">
				<div class="title-related-links">
					<h4>
						{{ $__t('Picture') }}
					</h4>
					<div class="form-group w-75 m-0">
						<div class="input-group">
							<div class="custom-file">
								<input type="file"
									class="custom-file-input"
									id="product-picture"
									accept="image/*">
								<label id="product-picture-label"
									class="custom-file-label @if(empty($product->picture_file_name)) d-none @endif"
									for="product-picture">
									{{ $product->picture_file_name }}
								</label>
								<label id="product-picture-label-none"
									class="custom-file-label @if(!empty($product->picture_file_name)) d-none @endif"
									for="product-picture">
									{{ $__t('No file selected') }}
								</label>
							</div>
							<div class="input-group-append">
								<span class="input-group-text"><i class="fas fa-trash"
										id="delete-current-product-picture-button"></i></span>
							</div>
						</div>
					</div>
				</div>
				@if(!empty($product->picture_file_name))
				<img id="current-product-picture"
					data-src="{{ $U('/api/files/productpictures/' . base64_encode($product->picture_file_name) . '?force_serve_as=picture&best_fit_width=400') }}"
					class="img-fluid img-thumbnail mt-2 lazy mb-5">
				<p id="delete-current-product-picture-on-save-hint"
					class="form-text text-muted font-italic d-none mb-5">{{ $__t('The current picture will be deleted when you save the product') }}</p>
				@else
				<p id="no-current-product-picture-hint"
					class="form-text text-muted font-italic mb-5">{{ $__t('No picture available') }}</p>
				@endif
			</div>
		</div>
	</div>
</div>
@stop
