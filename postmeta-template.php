	<label for="flattr_post_language"><?php echo __('Language:') ?></label>
	<select name="flattr_post_language" id="flattr_post_language">
	<?php
		foreach (Flattr::getLanguages() as $languageCode => $language)
		{
			printf('<option value="%s" %s>%s</option>',
				$languageCode,
				($languageCode == $selectedLanguage ? 'selected' : ''),
				$language
			);
		}
	?>
	</select>
	
	<br />
	
	<label for="flattr_post_category"><?php echo __('Category:') ?></label>
	<select name="flattr_post_category" id="flattr_post_category">
	<?php
		foreach (Flattr::getCategories() as $category)
		{
			printf('<option value="%s" %s>%s</option>',
				$category,
				($category == $selectedCategory ? 'selected' : ''),
				ucfirst($category)
			);
		}
	?>
	</select>
	
	<br />
	
	Disable the Flattr button on this post? <input type="checkbox" value="true" name="flattr_btn_disabled" <?php if ($btnDisabled) { echo 'checked="checked"'; } ?>/>
