<?php
/**
 * CategorySuggestionResponse
 *
 * PHP version 5
 *
 * @category Class
 * @package  Swagger\Client
 * @author   Swagger Codegen team
 * @link     https://github.com/swagger-api/swagger-codegen
 */

/**
 * Taxonomy API
 *
 * Use the Taxonomy API to discover the most appropriate eBay categories under which sellers can offer inventory items for sale, and the most likely categories under which buyers can browse or search for items to purchase. In addition, the Taxonomy API provides metadata about the required and recommended category aspects to include in listings, and also has two operations to retrieve parts compatibility information.
 *
 * OpenAPI spec version: v1.0.0
 * 
 * Generated by: https://github.com/swagger-api/swagger-codegen.git
 * Swagger Codegen version: 3.0.32
 */
/**
 * NOTE: This class is auto generated by the swagger code generator program.
 * https://github.com/swagger-api/swagger-codegen
 * Do not edit the class manually.
 */

namespace Swagger\Client\Model;

use \ArrayAccess;
use \Swagger\Client\ObjectSerializer;

/**
 * CategorySuggestionResponse Class Doc Comment
 *
 * @category Class
 * @description This type contains an array of suggested category tree nodes that are considered by eBay to most closely correspond to the keywords provided in a query string, from a specified category tree.
 * @package  Swagger\Client
 * @author   Swagger Codegen team
 * @link     https://github.com/swagger-api/swagger-codegen
 */
class CategorySuggestionResponse implements ModelInterface, ArrayAccess
{
    const DISCRIMINATOR = null;

    /**
      * The original name of the model.
      *
      * @var string
      */
    protected static $swaggerModelName = 'CategorySuggestionResponse';

    /**
      * Array of property to type mappings. Used for (de)serialization
      *
      * @var string[]
      */
    protected static $swaggerTypes = [
        'category_suggestions' => '\Swagger\Client\Model\CategorySuggestion[]',
'category_tree_id' => 'string',
'category_tree_version' => 'string'    ];

    /**
      * Array of property to format mappings. Used for (de)serialization
      *
      * @var string[]
      */
    protected static $swaggerFormats = [
        'category_suggestions' => null,
'category_tree_id' => null,
'category_tree_version' => null    ];

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @return array
     */
    public static function swaggerTypes()
    {
        return self::$swaggerTypes;
    }

    /**
     * Array of property to format mappings. Used for (de)serialization
     *
     * @return array
     */
    public static function swaggerFormats()
    {
        return self::$swaggerFormats;
    }

    /**
     * Array of attributes where the key is the local name,
     * and the value is the original name
     *
     * @var string[]
     */
    protected static $attributeMap = [
        'category_suggestions' => 'categorySuggestions',
'category_tree_id' => 'categoryTreeId',
'category_tree_version' => 'categoryTreeVersion'    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'category_suggestions' => 'setCategorySuggestions',
'category_tree_id' => 'setCategoryTreeId',
'category_tree_version' => 'setCategoryTreeVersion'    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'category_suggestions' => 'getCategorySuggestions',
'category_tree_id' => 'getCategoryTreeId',
'category_tree_version' => 'getCategoryTreeVersion'    ];

    /**
     * Array of attributes where the key is the local name,
     * and the value is the original name
     *
     * @return array
     */
    public static function attributeMap()
    {
        return self::$attributeMap;
    }

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @return array
     */
    public static function setters()
    {
        return self::$setters;
    }

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @return array
     */
    public static function getters()
    {
        return self::$getters;
    }

    /**
     * The original name of the model.
     *
     * @return string
     */
    public function getModelName()
    {
        return self::$swaggerModelName;
    }

    

    /**
     * Associative array for storing property values
     *
     * @var mixed[]
     */
    protected $container = [];

    /**
     * Constructor
     *
     * @param mixed[] $data Associated array of property values
     *                      initializing the model
     */
    public function __construct(array $data = null)
    {
        $this->container['category_suggestions'] = isset($data['category_suggestions']) ? $data['category_suggestions'] : null;
        $this->container['category_tree_id'] = isset($data['category_tree_id']) ? $data['category_tree_id'] : null;
        $this->container['category_tree_version'] = isset($data['category_tree_version']) ? $data['category_tree_version'] : null;
    }

    /**
     * Show all the invalid properties with reasons.
     *
     * @return array invalid properties with reasons
     */
    public function listInvalidProperties()
    {
        $invalidProperties = [];

        return $invalidProperties;
    }

    /**
     * Validate all the properties in the model
     * return true if all passed
     *
     * @return bool True if all properties are valid
     */
    public function valid()
    {
        return count($this->listInvalidProperties()) === 0;
    }


    /**
     * Gets category_suggestions
     *
     * @return \Swagger\Client\Model\CategorySuggestion[]
     */
    public function getCategorySuggestions()
    {
        return $this->container['category_suggestions'];
    }

    /**
     * Sets category_suggestions
     *
     * @param \Swagger\Client\Model\CategorySuggestion[] $category_suggestions Contains details about one or more suggested categories that correspond to the provided keywords. The array of suggested categories is sorted in order of eBay's confidence of the relevance of each category (the first category is the most relevant). Important: This call is not supported in the Sandbox environment. It will return a response payload in which the categoryName fields contain random or boilerplate text regardless of the query submitted.
     *
     * @return $this
     */
    public function setCategorySuggestions($category_suggestions)
    {
        $this->container['category_suggestions'] = $category_suggestions;

        return $this;
    }

    /**
     * Gets category_tree_id
     *
     * @return string
     */
    public function getCategoryTreeId()
    {
        return $this->container['category_tree_id'];
    }

    /**
     * Sets category_tree_id
     *
     * @param string $category_tree_id The unique identifier of the eBay category tree from which suggestions are returned.
     *
     * @return $this
     */
    public function setCategoryTreeId($category_tree_id)
    {
        $this->container['category_tree_id'] = $category_tree_id;

        return $this;
    }

    /**
     * Gets category_tree_version
     *
     * @return string
     */
    public function getCategoryTreeVersion()
    {
        return $this->container['category_tree_version'];
    }

    /**
     * Sets category_tree_version
     *
     * @param string $category_tree_version The version of the category tree identified by categoryTreeId. It's a good idea to cache this value for comparison so you can determine if this category tree has been modified in subsequent calls.
     *
     * @return $this
     */
    public function setCategoryTreeVersion($category_tree_version)
    {
        $this->container['category_tree_version'] = $category_tree_version;

        return $this;
    }
    /**
     * Returns true if offset exists. False otherwise.
     *
     * @param integer $offset Offset
     *
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    /**
     * Gets offset.
     *
     * @param integer $offset Offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }

    /**
     * Sets value based on offset.
     *
     * @param integer $offset Offset
     * @param mixed   $value  Value to be set
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    /**
     * Unsets offset.
     *
     * @param integer $offset Offset
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }

    /**
     * Gets the string presentation of the object
     *
     * @return string
     */
    public function __toString()
    {
        if (defined('JSON_PRETTY_PRINT')) { // use JSON pretty print
            return json_encode(
                ObjectSerializer::sanitizeForSerialization($this),
                JSON_PRETTY_PRINT
            );
        }

        return json_encode(ObjectSerializer::sanitizeForSerialization($this));
    }
}
