<?php
/*
*   Copyright 2014 Ruben R Aparicio
*
*   Licensed under the Apache License, Version 2.0 (the "License");
*   you may not use this file except in compliance with the License.
*   You may obtain a copy of the License at
*
*	http://www.apache.org/licenses/LICENSE-2.0
*
*   Unless required by applicable law or agreed to in writing, software
*   distributed under the License is distributed on an "AS IS" BASIS,
*   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
*   See the License for the specific language governing permissions and
*   limitations under the License.
*   
*/

class CategoryControllerCore extends FrontController
{
	public $php_self = 'category';
	protected $category;
	public $customer_access = true;

	/**
	 * Set default medias for this controller
	 */
	public function setMedia()
	{
		parent::setMedia();

		if ($this->context->getMobileDevice() == false)
		{
			//TODO : check why cluetip css is include without js file
			$this->addCSS(array(
				_THEME_CSS_DIR_.'scenes.css' => 'all',
				_THEME_CSS_DIR_.'category.css' => 'all',
				_THEME_CSS_DIR_.'product_list.css' => 'all',
			));

			if (Configuration::get('PS_COMPARATOR_MAX_ITEM') > 0)
				$this->addJS(_THEME_JS_DIR_.'products-comparison.js');
		}
	}

	public function canonicalRedirection($canonicalURL = '')
	{
		if (Tools::getValue('live_edit'))
			return ;
		if (!Validate::isLoadedObject($this->category) || !$this->category->inShop() || !$this->category->isAssociatedToShop())
		{
			$this->redirect_after = '404';
			$this->redirect();
		}
		if (!Tools::getValue('noredirect') && Validate::isLoadedObject($this->category))
			parent::canonicalRedirection($this->context->link->getCategoryLink($this->category));
	}

	/**
	 * Initialize category controller
	 * @see FrontController::init()
	 */
	public function init()
	{
		// Get category ID
		$id_category = (int)Tools::getValue('id_category');
		if (!$id_category || !Validate::isUnsignedId($id_category))
			$this->errors[] = Tools::displayError('Missing category ID');

		// Instantiate category
		$this->category = new Category($id_category, $this->context->language->id);

		parent::init();
		//check if the category is active and return 404 error if is disable.
		if (!$this->category->active)
		{
			header('HTTP/1.1 404 Not Found');
			header('Status: 404 Not Found');
		}
		//check if category can be accessible by current customer and return 403 if not
		if (!$this->category->checkAccess($this->context->customer->id))
		{
			header('HTTP/1.1 403 Forbidden');
			header('Status: 403 Forbidden');
			$this->errors[] = Tools::displayError('You do not have access to this category.');
			$this->customer_access = false;
		}
	}
	
	public function initContent()
	{
		parent::initContent();
		
		$this->setTemplate(_PS_THEME_DIR_.'category.tpl');
		
		if (!$this->customer_access)
			return;

		if (isset($this->context->cookie->id_compare))
			$this->context->smarty->assign('compareProducts', CompareProduct::getCompareProducts((int)$this->context->cookie->id_compare));

		$this->productSort(); // Product sort must be called before assignProductList()
		
		$this->assignScenes();
		$this->assignSubcategories();
		if ($this->category->id != 1)
			$this->assignProductList();

		$this->context->smarty->assign(array(
			'category' => $this->category,
			'products' => (isset($this->cat_products) && $this->cat_products) ? $this->cat_products : null,
			'id_category' => (int)$this->category->id,
			'id_category_parent' => (int)$this->category->id_parent,
			'return_category_name' => Tools::safeOutput($this->category->name),
			'path' => Tools::getPath($this->category->id),
			'add_prod_display' => Configuration::get('PS_ATTRIBUTE_CATEGORY_DISPLAY'),
			'categorySize' => Image::getSize(ImageType::getFormatedName('category')),
			'mediumSize' => Image::getSize(ImageType::getFormatedName('medium')),
			'thumbSceneSize' => Image::getSize(ImageType::getFormatedName('m_scene')),
			'homeSize' => Image::getSize(ImageType::getFormatedName('home')),
			'allow_oosp' => (int)Configuration::get('PS_ORDER_OUT_OF_STOCK'),
			'comparator_max_item' => (int)Configuration::get('PS_COMPARATOR_MAX_ITEM'),
			'suppliers' => Supplier::getSuppliers()
		));
	}

	/**
	 * Assign scenes template vars
	 */
	protected function assignScenes()
	{
		// Scenes (could be externalised to another controler if you need them)
		$scenes = Scene::getScenes($this->category->id, $this->context->language->id, true, false);
		$this->context->smarty->assign('scenes', $scenes);

		// Scenes images formats
		if ($scenes && ($sceneImageTypes = ImageType::getImagesTypes('scenes')))
		{
			foreach ($sceneImageTypes as $sceneImageType)
			{
				if ($sceneImageType['name'] == ImageType::getFormatedName('m_scene'))
					$thumbSceneImageType = $sceneImageType;
				elseif ($sceneImageType['name'] == ImageType::getFormatedName('scene'))
					$largeSceneImageType = $sceneImageType;
			}

			$this->context->smarty->assign(array(
				'thumbSceneImageType' => isset($thumbSceneImageType) ? $thumbSceneImageType : null,
				'largeSceneImageType' => isset($largeSceneImageType) ? $largeSceneImageType : null,
			));
		}
	}

	/**
	 * Assign sub categories templates vars
	 */
	protected function assignSubcategories()
	{
		if ($subCategories = $this->category->getSubCategories($this->context->language->id))
		{
			$subCategories = $this->checkSubcategoriesProducts($subCategories);
			$this->context->smarty->assign(array(
				'subcategories' => $subCategories,
				'subcategories_nb_total' => count($subCategories),
				'subcategories_nb_half' => ceil(count($subCategories) / 2)
			));
		}
	}
	
	private function checkSubcategoriesProducts($subcategories){
		
		for($i=0;$i<count($subcategories);$i++){
			$id_category = $subcategories[$i]['id_category'];
			$subcategories[$i]['has_products']=$this->checkCategoryHasProducts($id_category);
		}
		
		return $subcategories;
	}
	
	private function checkCategoryHasProducts($id_category){
		
		$ProductsCount = (int)Db::getInstance()->getValue('SELECT COUNT(cp.id_category) FROM '._DB_PREFIX_.'category_product cp, '._DB_PREFIX_.'product pr WHERE cp.id_category = '.$id_category .' AND cp.id_product = pr.id_product AND pr.active = 1' );
		if($ProductsCount!=0){
			return true;
		}else{
			$subcategories = Db::getInstance()->executeS('SELECT id_category FROM '._DB_PREFIX_.'category WHERE id_parent = '.$id_category);
			if(count($subcategories) != 0){
				foreach($subcategories as $cat){
					if($this->checkCategoryHasProducts($cat['id_category'])){
						return true;
					}
				}
			}
			return false;
		}
	}

	/**
	 * Assign list of products template vars
	 */
	public function assignProductList()
	{
		$hookExecuted = false;
		Hook::exec('actionProductListOverride', array(
			'nbProducts' => &$this->nbProducts,
			'catProducts' => &$this->cat_products,
			'hookExecuted' => &$hookExecuted,
		));

		// The hook was not executed, standard working
		if (!$hookExecuted)
		{
			$this->context->smarty->assign('categoryNameComplement', '');
			$this->nbProducts = $this->category->getProducts(null, null, null, $this->orderBy, $this->orderWay, true);
			$this->pagination((int)$this->nbProducts); // Pagination must be call after "getProducts"
			$this->cat_products = $this->category->getProducts($this->context->language->id, (int)$this->p, (int)$this->n, $this->orderBy, $this->orderWay);
		}
		// Hook executed, use the override
		else
			// Pagination must be call after "getProducts"
			$this->pagination($this->nbProducts);

		foreach ($this->cat_products as &$product)
		{
			if ($product['id_product_attribute'] && isset($product['product_attribute_minimal_quantity']))
				$product['minimal_quantity'] = $product['product_attribute_minimal_quantity'];
		}

		$this->context->smarty->assign('nb_products', $this->nbProducts);
	}
	
	/**
	 * Get instance of current category
	 */
	public function getCategory()
	{
		return $this->category;
	}
}

