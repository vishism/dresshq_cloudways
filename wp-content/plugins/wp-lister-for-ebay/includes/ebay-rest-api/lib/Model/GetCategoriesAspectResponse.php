<?php
/**
 * GetCategoriesAspectResponse
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
 * GetCategoriesAspectResponse Class Doc Comment
 *
 * @category Class
 * @package  Swagger\Client
 * @author   Swagger Codegen team
 * @link     https://github.com/swagger-api/swagger-codegen
 */
class GetCategoriesAspectResponse implements ModelInterface, ArrayAccess
{
    const DISCRIMINATOR = null;

    /**
      * The original name of the model.
      *
      * @var string
      */
    protected static $swaggerModelName = 'GetCategoriesAspectResponse';

    /**
      * Array of property to type mappings. Used for (de)serialization
      *
      * @var string[]
      */
    protected static $swaggerTypes = [
        'category_tree_id' => 'string',
'category_tree_version' => 'string',
'category_aspects' => '\Swagger\Client\Model\CategoryAspect[]'    ];

    /**
      * Array of property to format mappings. Used for (de)serialization
      *
      * @var string[]
      */
    protected static $swaggerFormats = [
        'category_tree_id' => null,
'category_tree_version' => null,
'category_aspects' => null    ];

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
        'category_tree_id' => 'categoryTreeId',
'category_tree_version' => 'categoryTreeVersion',
'category_aspects' => 'categoryAspects'    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'category_tree_id' => 'setCategoryTreeId',
'category_tree_version' => 'setCategoryTreeVersion',
'category_aspects' => 'setCategoryAspects'    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'category_tree_id' => 'getCategoryTreeId',
'category_tree_version' => 'getCategoryTreeVersion',
'category_aspects' => 'getCategoryAspects'    ];

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
        $this->container['category_tree_id'] = isset($data['category_tree_id']) ? $data['category_tree_id'] : null;
        $this->container['category_tree_version'] = isset($data['category_tree_version']) ? $data['category_tree_version'] : null;
        $this->container['category_aspects'] = isset($data['category_aspects']) ? $data['category_aspects'] : null;
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
     * @param string $category_tree_id The unique identifier of the eBay category tree being requested.
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
     * @param string $category_tree_version The version of the category tree that is returned in the categoryTreeId field.
     *
     * @return $this
     */
    public function setCategoryTreeVersion($category_tree_version)
    {
        $this->container['category_tree_version'] = $category_tree_version;

        return $this;
    }

    /**
     * Gets category_aspects
     *
     * @return \Swagger\Client\Model\CategoryAspect[]
     */
    public function getCategoryAspects()
    {
        return $this->container['category_aspects'];
    }

    /**
     * Sets category_aspects
     *
     * @param \Swagger\Client\Model\CategoryAspect[] $category_aspects An array of aspects that are appropriate or necessary for accurately describing items in a particular leaf category.
     *
     * @return $this
     */
    public function setCategoryAspects($category_aspects)
    {
        $this->container['category_aspects'] = $category_aspects;

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
