private function products()
	{
		if(!CModule::IncludeModule("iblock")) return false;
		if(!CModule::IncludeModule("catalog")) return false;

		if(empty($this->iblockId)) return false;
        if(empty($this->siteURL)) return false;

		$arSelect = Array('ID', 'XML_ID', 'PREVIEW_PICTURE', 'DETAIL_PICTURE');
		$arFilter = Array("IBLOCK_ID"=>$this->iblockId);
		$res = CIBlockElement::GetList(
		    Array("ID" => "ASC"),
		    $arFilter,
		    false,
		    false,
		    $arSelect
		    );
		  
		while($ob = $res->GetNextElement())
		    {
		    $arFields = $ob->GetFields();
		    $pictures[$arFields['ID']]['PREVIEW_PICTURE'] = $arFields['PREVIEW_PICTURE'];
		    $pictures[$arFields['ID']]['DETAIL_PICTURE'] = $arFields['DETAIL_PICTURE'];
		    $arIdProd[$arFields['XML_ID']] = $arFields['ID'];
		    }


		// if(empty($_SESSION['lastid']['products'])){
		// 	$lastId = 0;
		// } else {
		// 	$lastId = $_SESSION['lastid']['products'];
		// }
		//Тянем недостающие элементы
		$Products = $this->send("get", "products", $lastId);
		// pp($Products);

		// if(!empty($Products)) {
		// 	$_SESSION['lastid']['products'] = max(array_keys($Products));
		// 	echo '<script> startCountdown(); function reload (){document.location.href = location.href};setTimeout("reload()", 2000); </script>';
		// } else {
		// 	$_SESSION['lastid']['products'] = 0;
		// 	echo 'Все необходимые элементы добавлены и обновлены<br>';
		// }

		$arFilter = array("IBLOCK_ID" =>$this->iblockId);
		$arSort = array("ID" => "ASC");
		$uf_name = array("UF_ORIGINAL_ID");
		
		$rsSections = CIBlockSection::GetList($arSort, $arFilter, false, $uf_name);
		while($arSection = $rsSections->GetNext())
		{
			$arIdSec[$arSection['UF_ORIGINAL_ID']] = $arSection['ID'];
		}

		$countAdd = 0;
		$countUpdate = 0;
		if(!empty($Products)) {
			foreach ($Products as $kProd => $vProd) {
				if(array_key_exists($vProd["ID"], $arIdProd)){
					$ID = $arIdProd[$vProd["ID"]];
				} else {
					$ID = 0;
				}

				if(array_key_exists($vProd["IBLOCK_SECTION_ID"], $arIdSec)){
					$IBLOCK_SECTION_ID = $arIdSec[$vProd["IBLOCK_SECTION_ID"]];
				} else {
					$IBLOCK_SECTION_ID = 0;
				}

				$el = new CIBlockElement;

				$code = randString(2);
				$arFieldsP = Array(
					"SYNC" => true,
					"IBLOCK_SECTION_ID" => $IBLOCK_SECTION_ID,
					"IBLOCK_ID"     	=> $this->iblockId,

					"NAME"         	 	=> htmlspecialchars_decode($vProd['NAME']),
					"CODE"			 	=> $vProd['CODE'].$code,		
					"ACTIVE"         	=> $vProd['ACTIVE'],
					"PREVIEW_TEXT"		=> htmlspecialchars_decode($vProd['PREVIEW_TEXT']),		
					"DETAIL_TEXT"      	=> htmlspecialchars_decode($vProd['DETAIL_TEXT'])
					);

				if(!empty($vProd['PREVIEW_PICTURE']) && empty($pictures[$ID]['PREVIEW_PICTURE'])){
					$PREV_PIC = $this->copyFile($vProd['PREVIEW_PICTURE']);
					$arFieldsP['PREVIEW_PICTURE'] = CFile::MakeFileArray($PREV_PIC);
				}
				if(!empty($vProd['DETAIL_PICTURE']) && empty($pictures[$ID]['DETAIL_PICTURE'])){
					$DETAIL_PIC = $this->copyFile($vProd['DETAIL_PICTURE']);
					$arFieldsP['DETAIL_PICTURE'] = CFile::MakeFileArray($DETAIL_PIC);
				}

				if($ID > 0)
				{
					$res = $el->Update($ID, $arFieldsP);
					// if ($res){
					// 	// echo 'Элемент обновлен ID: '.$ID.'<br>';
						$countUpdate++;

					// 	$PROPERTY_CODE = "ORIGINAL_URL";
					// 	$PROPERTY_VALUE = $this->siteURL.$vProd["DETAIL_PAGE_URL"];
					// 	CIBlockElement::SetPropertyValuesEx($ID, false, array($PROPERTY_CODE => $PROPERTY_VALUE));

					// 	$PROPERTY_CODE = "ORIGINAL_SEC_ID";
					// 	$PROPERTY_VALUE = $vProd['IBLOCK_SECTION_ID'];
					// 	CIBlockElement::SetPropertyValuesEx($ID, false, array($PROPERTY_CODE => $PROPERTY_VALUE));


					// 	$ar_res = CCatalogProduct::GetByID($ID);

					// 	if(empty($ar_res)){

					// 		$arFieldsProd = array(
					// 			"SYNC" => true,
					// 			"ID" 			=> $ID,
					// 			"AVAILABLE"		=> 'Y',
					// 			"TYPE"			=> 1
					// 			);

					//       	CCatalogProduct::update($ID, $arFieldsProd);

					// 		$arFieldsPr = array(
					// 		"SYNC" => true,
					// 		"PRODUCT_ID" => $ID
					// 		);

					// 		CPrice::update($arFieldsPr);
					//     }
					// } 

				}
				  else
				{
					$ID = $el->Add($arFieldsP);
					$res = ($ID > 0);
					if ($res){
						// echo 'Элемент добавлен ID: '.$ID.'<br>';
						$countAdd++;

						$PROPERTY_CODE = "ORIGINAL_ID";
						$PROPERTY_VALUE = $vProd["ID"];
						CIBlockElement::SetPropertyValuesEx($ID, false, array($PROPERTY_CODE => $PROPERTY_VALUE));

						$PROPERTY_CODE = "ORIGINAL_SEC_ID";
						$PROPERTY_VALUE = $vProd['IBLOCK_SECTION_ID'];
						CIBlockElement::SetPropertyValuesEx($ID, false, array($PROPERTY_CODE => $PROPERTY_VALUE));

						$PROPERTY_CODE = "ORIGINAL_URL";
						$PROPERTY_VALUE = $this->siteURL.$vProd["DETAIL_PAGE_URL"];
						CIBlockElement::SetPropertyValuesEx($ID, false, array($PROPERTY_CODE => $PROPERTY_VALUE));

						$ar_res = CCatalogProduct::GetByID($ID);

						if(empty($ar_res)){

							$arFieldsProd = array(
								"SYNC" => true,
								"ID" 			=> $ID,
								"AVAILABLE"		=> 'Y',
								"TYPE"			=> 1
								);

					      	CCatalogProduct::Add($arFieldsProd);

							$arFieldsPr = array(
								"SYNC" => true,
								"PRODUCT_ID" => $ID
							);
							CPrice::Add($arFieldsPr);
					    }
					}
				}
				if(!$res) echo $el->LAST_ERROR;

			}
		}
		if(count($arIdProd)>0) echo count($arIdProd).' элементов в базе<br>';
		if($countAdd) echo 'Добавлено '.$countAdd.' элементов<br>';
		if($countUpdate) echo 'Обновлено '.$countUpdate.' элементов<br>';

	}
