<?php

/**
 * requests like "/?rex-api-call=fa_iconpicker[&method=<METHOD>]" | add further url params as you need
 * @author Peter Schulze | p.schulze@bitshifters.de
 */
class rex_api_fa_iconpicker extends rex_api_function
{
    protected $published = false;  // Aufruf aus dem Frontend erlaubt

    function execute() {
        $method = rex_request("method");

        switch($method) {
            /**
             * get available weights by package
             */
            case 'get-available-weights':
                try {
                    $search = [];

                    $subset = rex_fa_iconpicker::getActiveSubset();
                    $params = [
                        ':version' => rex_fa_iconpicker::getActiveVersion(),
                        ':variant' => rex_fa_iconpicker::getActiveVariant()
                    ];

                    if(!is_null($subset)) {
                        $params[':subset'] = $subset;
                    }

                    if(trim(rex_request("icons")) != "") {
                        $icons = preg_split("/[\s,]+/", trim(rex_request("icons")));

                        if(count($icons) > 0) {
                            $iconSearch = "";

                            foreach($icons as $idx => $icon) {
                                if(trim($icon) == "") {
                                    continue;
                                }

                                $iconSearch .= ($iconSearch != "" ? " OR " : "")."name LIKE :icon$idx";
                                $params[":icon$idx"] = str_replace("*", "%", trim($icon));
                            }

                            if($iconSearch != "") {
                                $search[] = "(".$iconSearch.")";
                            }
                        }
                    }

                    $weightsInSet = rex_sql::factory()->getArray("
                        SELECT
                            DISTINCT weight
                        FROM
                            " . rex::getTable('fa_icons') . " icons
                        WHERE
                            variant = :variant AND
                            version = :version AND
                            subset ".($subset == NULL ? "IS NULL" : "= :subset")."
                            ".(count($search) > 0 ? ' AND '.implode(" AND ", $search) : '')."
                        ORDER BY
                            FIELD(weight, 'T', 'L', 'R', 'S', 'D', 'B')
                    ", $params);


                    $allowedWeights = str_split(rex_request("weights"));
                    $weights = "";

                    foreach($weightsInSet as $item) {
                        if(in_array($item['weight'], $allowedWeights)) {
                            $weights .= $item['weight'];
                        }
                    }

                    rex_response::setStatus(rex_response::HTTP_OK);
                    rex_response::sendContent($weights, 'text/plain');
                } catch(Exception $e) {
                    rex_response::setStatus(rex_response::HTTP_INTERNAL_ERROR);
                    rex_response::sendContent(json_encode([
                        'errorcode' => 1,
                        'message' => 'SQL-Fehler: '. $e->getMessage(),
                    ]), 'application/json');
                }

                exit();

            /**
             * get svg code for icon id
             */
            case 'get-icon-svg':
                try {
                    $iconSVG = rex_sql::factory()->getArray("
                        SELECT
                            svg
                        FROM
                            " . rex::getTable('fa_icons') . " icons
                        WHERE
                            id = :id
                    ", [':id' => rex_request("icon-id")]);

                    if(isset($iconSVG[0]['svg'])) {
                        rex_response::setStatus(rex_response::HTTP_OK);
                        rex_response::sendContent($iconSVG[0]['svg'], 'text/html');
                    } else {
                        throw new Exception("cannot find SVG code for icon id: ". rex_request("icon-id", "int", -1));
                    }
                } catch(Exception $e) {
                    rex_response::setStatus(rex_response::HTTP_INTERNAL_ERROR);
                    rex_response::sendContent(json_encode([
                        'errorcode' => 1,
                        'message' => $e->getMessage(),
                    ]), 'application/json');
                }

                exit();

            /**
             * search icons > load pages
             */
            default:
                $search = [];

                $subset = rex_fa_iconpicker::getActiveSubset();
                $params = [
                    ':weight' => rex_request('preview-weight', "string"),
                    ':variant' => rex_fa_iconpicker::getActiveVariant(),
                    ':version' => rex_fa_iconpicker::getActiveVersion(),
                ];

                if(!is_null($subset)) {
                    $params[':subset'] = $subset;
                }

                $userSearch = trim(rex_request("icon-search", "string", ""));

                if($userSearch != "") {
                    $params[':search'] = "%$userSearch%";
                    $userSearch = "(`name` LIKE :search OR `label` LIKE :search OR `code` LIKE :search OR JSON_SEARCH(searchterms, 'one', :search) IS NOT NULL)";
                }

                // custom icons
                $icons = preg_split("/[\s,]+/", trim(rex_request("icons")));

                if(count($icons) > 0) {
                    $iconSearch = "";

                    foreach($icons as $idx => $icon) {
                        if(trim($icon) == "") {
                            continue;
                        }

                        $iconSearch .= ($iconSearch != "" ? " OR " : "")."name LIKE :icon$idx";
                        $params[":icon$idx"] = str_replace("*", "%", trim($icon));
                    }

                    if($iconSearch != "") {
                        $search[] = "(".$iconSearch.")";
                    }
                }

                $rows = rex_request('rows', "int");
                $cols = rex_request('columns', "int");
                $offset = rex_request('offset', "int", 0);
                $iconPage = rex_request('icon-page', "int", 0);

                $offsetCalculated = ($iconPage - $offset) * $rows * $cols;
                $offsetCalculated = $offsetCalculated < 0 ? 0 : $offsetCalculated;

                $limit = $rows * $cols * ($offset > 0 ? ($iconPage == 0 ? 1 : 2) * $offset + 1 : 1);
                $limit = "$offsetCalculated, $limit";

                $orderBy = rex_request('sort-by', "string")." ".rex_request('sort-direction', "string").", name ASC";

                $sql = "
                SELECT
                    id,
                    `name`,
                    code,
                    weight,
                    variant,
                    version,
                    label,
                    searchterms,
                    SHA1(svg) AS `svg-hash`,
                    (SELECT
                        GROUP_CONCAT(other_icons.weight)
                     FROM
                        ".rex::getTable('fa_icons')." other_icons
                     WHERE
                        other_icons.`name` = icons.`name` AND
                        other_icons.`variant` = icons.`variant` AND
                        other_icons.`version` = icons.`version` AND
                        other_icons.`subset` ".($subset == NULL ? "IS NULL" : "= icons.`subset`")."
                     ORDER BY
                        FIELD(other_icons.weight, 'T', 'L', 'R', 'S', 'D', 'B')
                    ) AS allweights
                FROM
                    ".rex::getTable('fa_icons')." icons
                WHERE
                    weight = :weight AND
                    variant = :variant AND
                    version = :version AND
                    subset ".($subset == NULL ? "IS NULL" : "= :subset")."
                    ".($userSearch != "" ? " AND $userSearch" : "")."
                    ".(count($search) > 0 ? ' AND '.implode(" AND ", $search) : '');

                try {
                    $countIcons = rex_sql::factory()->getArray("
                        SELECT
                            COUNT(*) AS cnt
                        FROM
                            ".rex::getTable('fa_icons')."
                        WHERE
                            weight = :weight AND
                            variant = :variant AND
                            version = :version AND
                            subset ".($subset == NULL ? "IS NULL" : "= :subset")."
                            ".($userSearch != "" ? " AND $userSearch" : "")."
                            ".(count($search) > 0 ? ' AND '.implode(" AND ", $search) : '')
                    , $params);

                    $icons = rex_sql::factory()->getArray("$sql ORDER BY $orderBy LIMIT $limit", $params);

                    $result = new stdClass();
                    $result->iconCount = (int)$countIcons[0]['cnt'];
                    $result->icons = $icons;
                    $result->page = $iconPage;
                    $result->limit = $limit;

                    rex_response::setStatus(rex_response::HTTP_OK);
                    rex_response::sendContent(json_encode($result), 'application/json');
                } catch(Exception $e) {
                    rex_response::setStatus(rex_response::HTTP_INTERNAL_ERROR);
                    rex_response::sendContent(json_encode([
                        'errorcode' => 1,
                        'message' => 'SQL-Fehler: '. $e->getMessage(),
                    ]), 'application/json');
                }

                exit();
        }
    }
}