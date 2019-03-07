<?php namespace Initbiz\LeafletPro\Models;

use Lang;
use Model;
use RainLab\Location\Models\Country;
use Initbiz\CumulusCore\Models\Cluster;
use Initbiz\LeafletPro\Classes\AddressResolver;
use October\Rain\Exception\ApplicationException;
use Initbiz\LeafletPro\Contracts\AddressObjectInterface;

/**
 * Marker Model
 */
class Marker extends Model implements AddressObjectInterface
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'initbiz_leafletpro_markers';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'country' => [
            Country::class,
            'table' => 'rainlab_location_countries',
        ]
    ];

    public function filterFields($fields, $context = null)
    {
        if (isset($fields->cluster_id) && $fields->cluster_id->value !== null) {
            $cluster = Cluster::find($fields->cluster_id->value);

            if (empty($fields->street->value)) {
                $fields->street->value = $cluster->thoroughfare;
            }

            if (empty($fields->postal_code->value)) {
                $fields->postal_code->value = $cluster->postal_code;
            }

            if (empty($fields->city->value)) {
                $fields->city->value = $cluster->city;
            }

            if (empty($fields->country_id->value)) {
                if ($cluster->country()->first()) {
                    $countryId = $cluster->country()->first()->id;
                    $fields->country_id->value = $countryId;
                }
            }
        }
    }

    public function getCountryIdOptions()
    {
        return Country::all()->pluck('name', 'id')->toArray();
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function refreshLongLat()
    {
        $addressResolver = new AddressResolver();

        $response = $addressResolver->resolv($this);

        if (empty($response)) {
            throw new ApplicationException(Lang::get('initbiz.leafletpro::lang.exceptions.address_resolver_empty_response'));
        }

        //TODO: to consider pop up with other possibilities, right now getting first element
        $address = $response[0];

        $this->lat = $address['lat'];
        $this->long = $address['lon'];
    }

    public function getLongLat()
    {
        return [
            'long' => $this->long,
            'lat' => $this->lat,
        ];
    }

    public function getStreet()
    {
        return $this->street;
    }

    public function getPostalCode()
    {
        return $this->postal_code;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function getCountry()
    {
        return $this->country()->first()->name;
    }
}
