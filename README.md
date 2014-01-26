README
======

This is an override file for the Category Crontroller and category template of **Prestashop**. The base of these overrides are the original files from **Prestashop**.

The aim of this modifications is to hide empty subcategories when showing the products and subcategories of a category.

You can download it from here: [Empty-Categories-Controller](http://zvblog.es/?wpdmact=process&did=Mi5ob3RsaW5r)

How to use:
-----------
- Move the file in _override/controllers/front/CategoryController.php_ to the same location in your **Prestashop** installation.
- Move the file in _themes/your_theme/category.tpl_ to the same location in your **Prestashop** installation, changing _your_theme_ for the theme that you are using in **Prestashop**.

###Warning###
Remember to make a copy if you overwrite any file.

These files has been done and tested using Prestashop 1.5.6.2 and the _"How to use"_ before is intented to be used when you are using the default theme (or very similar theme based on it) without any modification on the **"category.tpl"** file and with no existing **"CategoryController.php"** inside the override folder.

If that is not your case, you could check the [wiki](https://github.com/rubrodapa/Empty-Categories-Controller/wiki) where I explain what changes I made to the original files so you can try to make this changes in your shop.

###Tested on:###

- Prestashop 1.5.6.2
- Prestashop 1.5.6.1

> Copyright 2014 Ruben R Aparicio

>Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

>    http://www.apache.org/licenses/LICENSE-2.0

>Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
