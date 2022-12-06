# AspectConstraint

## Properties
Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**aspect_applicable_to** | **string[]** | This value indicate if the aspect identified by the aspects.localizedAspectName field is a product aspect (relevant to catalog products in the category) or an item/instance aspect, which is an aspect whose value will vary based on a particular instance of the product. | [optional] 
**aspect_data_type** | **string** | The data type of this aspect. For implementation help, refer to &lt;a href&#x3D;&#x27;https://developer.ebay.com/api-docs/commerce/taxonomy/types/txn:AspectDataTypeEnum&#x27;&gt;eBay API documentation&lt;/a&gt; | [optional] 
**aspect_enabled_for_variations** | **bool** | A value of true indicates that this aspect can be used to help identify item variations. | [optional] 
**aspect_format** | **string** | Returned only if the value of aspectDataType identifies a data type that requires specific formatting. Currently, this field provides formatting hints as follows: DATE: YYYY, YYYYMM, YYYYMMDD NUMBER: int32, double | [optional] 
**aspect_max_length** | **int** | The maximum length of the item/instance aspect&#x27;s value. The seller must make sure not to exceed this length when specifying the instance aspect&#x27;s value for a product. This field is only returned for instance aspects. | [optional] 
**aspect_mode** | **string** | The manner in which values of this aspect must be specified by the seller (as free text or by selecting from available options). For implementation help, refer to &lt;a href&#x3D;&#x27;https://developer.ebay.com/api-docs/commerce/taxonomy/types/txn:AspectModeEnum&#x27;&gt;eBay API documentation&lt;/a&gt; | [optional] 
**aspect_required** | **bool** | A value of true indicates that this aspect is required when offering items in the specified category. | [optional] 
**aspect_usage** | **string** | The enumeration value returned in this field will indicate if the corresponding aspect is recommended or optional. Note: This field is always returned, even for hard-mandated/required aspects (where aspectRequired: true). The value returned for required aspects will be RECOMMENDED, but they are actually required and a seller will be blocked from listing or revising an item without these aspects. For implementation help, refer to &lt;a href&#x3D;&#x27;https://developer.ebay.com/api-docs/commerce/taxonomy/types/txn:AspectUsageEnum&#x27;&gt;eBay API documentation&lt;/a&gt; | [optional] 
**expected_required_by_date** | **string** | The expected date after which the aspect will be required. Note: The value returned in this field specifies only an approximate date, which may not reflect the actual date after which the aspect is required. | [optional] 
**item_to_aspect_cardinality** | **string** | Indicates whether this aspect can accept single or multiple values for items in the specified category. For implementation help, refer to &lt;a href&#x3D;&#x27;https://developer.ebay.com/api-docs/commerce/taxonomy/types/txn:ItemToAspectCardinalityEnum&#x27;&gt;eBay API documentation&lt;/a&gt; | [optional] 

[[Back to Model list]](../../README.md#documentation-for-models) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to README]](../../README.md)

