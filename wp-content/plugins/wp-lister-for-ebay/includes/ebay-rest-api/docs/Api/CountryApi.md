# Swagger\Client\CountryApi

All URIs are relative to *https://api.ebay.com{basePath}*

Method | HTTP request | Description
------------- | ------------- | -------------
[**getSalesTaxJurisdictions**](CountryApi.md#getsalestaxjurisdictions) | **GET** /country/{countryCode}/sales_tax_jurisdiction | 

# **getSalesTaxJurisdictions**
> \Swagger\Client\Model\SalesTaxJurisdictions getSalesTaxJurisdictions($country_code)



This method retrieves all the sales tax jurisdictions for the country that you specify in the <b>countryCode</b> path parameter. Countries with valid sales tax jurisdictions are Canada and the US.  <br><br>The response from this call tells you the jurisdictions for which a seller can configure tax tables. Although setting up tax tables is optional, you can use the <b>createOrReplaceSalesTax</b> in the <b>Account API</b> call to configure the tax tables for the jurisdictions you sell to.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure OAuth2 access token for authorization: api_auth
$config = Swagger\Client\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');

$apiInstance = new Swagger\Client\Api\CountryApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$country_code = "country_code_example"; // string | This path parameter specifies the two-letter <a href=\"https://www.iso.org/iso-3166-country-codes.html\" title=\"https://www.iso.org\" target=\"_blank\">ISO 3166</a> country code for the country whose jurisdictions you want to retrieve. eBay provides sales tax jurisdiction information for Canada and the United States.Valid values for this path parameter are <code>CA</code> and <code>US</code>.

try {
    $result = $apiInstance->getSalesTaxJurisdictions($country_code);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CountryApi->getSalesTaxJurisdictions: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **country_code** | **string**| This path parameter specifies the two-letter &lt;a href&#x3D;\&quot;https://www.iso.org/iso-3166-country-codes.html\&quot; title&#x3D;\&quot;https://www.iso.org\&quot; target&#x3D;\&quot;_blank\&quot;&gt;ISO 3166&lt;/a&gt; country code for the country whose jurisdictions you want to retrieve. eBay provides sales tax jurisdiction information for Canada and the United States.Valid values for this path parameter are &lt;code&gt;CA&lt;/code&gt; and &lt;code&gt;US&lt;/code&gt;. |

### Return type

[**\Swagger\Client\Model\SalesTaxJurisdictions**](../Model/SalesTaxJurisdictions.md)

### Authorization

[api_auth](../../README.md#api_auth)

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

