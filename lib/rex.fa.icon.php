<?php
/**
 * @date     22.02.2021 11:29
 * @author   Peter Schulze [p.schulze@bitshifters.de]
 */

class rex_fa_icon
{
    /**
     * rex_fa_icon constructor.
     * @author Peter Schulze | p.schulze[at]bitshifters.de
     */
    public function __construct($data) {
        foreach($data as $field => $value) {
            $this->$field = ($field == 'id' ? intval($value) : $value);
        }
    }

    /**
     * @return int
     * @author Peter Schulze | p.schulze[at]bitshifters.de
     */
    public function getID(): int {
        return $this->id;
    }

    /**
     * @return string
     * @author Peter Schulze | p.schulze[at]bitshifters.de
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return string
     * @author Peter Schulze | p.schulze[at]bitshifters.de
     */
    public function getCode(): string {
        return $this->code;
    }

    /**
     * @return string
     * @author Peter Schulze | p.schulze[at]bitshifters.de
     */
    public function getLabel(): string {
        return $this->label;
    }

    /**
     * @return array
     * @author Peter Schulze | p.schulze[at]bitshifters.de
     */
    public function getWeight(): array {
        return $this->weight;
    }

    /**
     * @return string
     * @author Peter Schulze | p.schulze[at]bitshifters.de
     */
    public function getVariant() {
        return $this->variant;
    }

    /**
     * @return string
     * @author Peter Schulze | p.schulze[at]bitshifters.de
     */
    public function getVersion() {
        return $this->version;
    }

    /**
     * @return string
     * @author Peter Schulze | p.schulze[at]bitshifters.de
     */
    public function getSearchTerms() {
        return $this->searchterms;
    }

    /**
     * @return int (timestamp)
     * @author Peter Schulze | p.schulze[at]bitshifters.de
     */
    public function getCreateDate(): int {
        return strftime($this->createdate);
    }

    /**
     * @return string
     * @author Peter Schulze | p.schulze[at]bitshifters.de
     */
    public function getCreateUser(): string {
        return $this->createuser;
    }

    /**
     * @return string
     * @author Peter Schulze | p.schulze[at]bitshifters.de
     */
    public function getSvg(): string {
        return $this->svg;
    }

    /**
     * get icon object by code
     *
     * @param string $code
     * @param string|null $weight
     * @param string|null $variant
     * @param string|null $version
     * @return rex_fa_icon|null
     * @throws rex_sql_exception
     * @author Peter Schulze | p.schulze[at]bitshifters.de
     */
    public static function getByCode(string $code, ?string $weight, ?string $variant, ?string $version): ?rex_fa_icon {
        $search = [':code' => $code];
        $searchWeight = false;

        if(is_null($weight)) {
            $searchWeight = true;
            $search[':weight'] = $weight;
        }

        $search[':variant'] = (is_null($variant) ? rex_fa_iconpicker::getActiveVariant() : $variant);
        $search[':version'] = (is_null($version) ? rex_fa_iconpicker::getActiveVersion() : $version);

        $iconData = rex_sql::factory()->getArray("
            SELECT * FROM ".rex::getTable('fa_icons')."
            WHERE
                code = :code AND
                variant = :variant AND
                version = :version
                ".($searchWeight ? 'weight = :weight' : '')."
            ORDER BY
                FIELD(weight, 'T', 'L', 'R', 'S', 'D', 'B')
            LIMIT 1
        ", $search);

        if(isset($iconData[0])) {
            return new rex_fa_icon($iconData[0]);
        }

        return null;
    }

    /**
     * get icon object by name
     *
     * @param string $name
     * @param string|null $weight
     * @param string|null $variant
     * @param string|null $version
     * @return rex_fa_icon|null
     * @throws rex_sql_exception
     * @author Peter Schulze | p.schulze[at]bitshifters.de
     */
    public static function getByName(string $name, ?string $weight, ?string $variant, ?string $version): ?rex_fa_icon {
        $search = [':name' => $name];
        $searchWeight = false;

        if(is_null($weight)) {
            $searchWeight = true;
            $search[':weight'] = $weight;
        }

        $search[':variant'] = (is_null($variant) ? rex_fa_iconpicker::getActiveVariant() : $variant);
        $search[':version'] = (is_null($version) ? rex_fa_iconpicker::getActiveVersion() : $version);

        $iconData = rex_sql::factory()->getArray("
            SELECT * FROM ".rex::getTable('fa_icons')."
            WHERE
                `name` = :name AND
                variant = :variant AND
                version = :version
                ".($searchWeight ? 'weight = :weight' : '')."
            ORDER BY
                FIELD(weight, 'T', 'L', 'R', 'S', 'D', 'B')
            LIMIT 1
        ", $search);

        if(isset($iconData[0])) {
            return new rex_fa_icon($iconData[0]);
        }

        return null;
    }
}