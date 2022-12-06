# CompatibilityProperty

## Properties
Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**name** | **string** | This is the actual name of the compatible vehicle property as it is known on the specified eBay marketplace and in the eBay category. This is the string value that should be used in the compatibility_property and filter query parameters of a getCompatibilityPropertyValues request URI. Typical vehicle properties are &#x27;Make&#x27;, &#x27;Model&#x27;, &#x27;Year&#x27;, &#x27;Engine&#x27;, and &#x27;Trim&#x27;, but will vary based on the eBay marketplace and the eBay category. | [optional] 
**localized_name** | **string** | This is the localized name of the compatible vehicle property. The language that is used will depend on the user making the call, or based on the language specified if the Content-Language HTTP header is used. In some instances, the string value in this field may be the same as the string in the corresponding name field. | [optional] 

[[Back to Model list]](../../README.md#documentation-for-models) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to README]](../../README.md)

