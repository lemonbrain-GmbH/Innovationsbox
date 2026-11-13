<?php

declare(strict_types=1);

namespace Application\Block\JlSearchResults;

use Concrete\Core\Block\BlockController;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Config\Repository\Repository as Config;
use Concrete\Core\Package\PackageService;
use Concrete\Core\Page\Page;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Support\Facade\Database;
use Concrete\Package\JlSearchPlusPlus\Block\JlSearchResults\Controller as JlSearchResultsController;
use JtF\SearchPlusPlus\CacheHelper;
use JtF\SearchPlusPlus\DebugOutput;
use JtF\SearchPlusPlus\ExtendedSearch;
use JtF\SearchPlusPlus\Highlight;
use JtF\SearchPlusPlus\SearchResultLogging;
use JtF\SearchPlusPlus\SearchSecurity;
use JtF\SearchPlusPlus\SearchWordNote;

if (class_exists('Concrete\Package\JlSearchPlusPlus\Block\JlSearchResults\Controller')) {

    /*
     * John Liddiard (aka JohntheFish) www.c5magic.co.uk
     * This package contains software copyright and proprietary to John Liddiard
     *
     */

    class Controller extends JlSearchResultsController
    {
        public const RESULTS = 'results';
        public const RESULTS_CONTENT = 'results_content';
        public const RESULTS_LINK_URL = 'link_url';
        public const RESULTS_LINK_TARGET = 'link_target';
        public const RESULTS_LINK_TEXT = 'link_text';
        public const EXPRESS_HANDLER = 'handler';
        public const EXPRESS_LINK = 'link';
        public const EXPRESS_ID = 'id';
        public const EXPRESS_CONTENT = 'content';
        public const EXPRESS_TITLE = 'title';
        public const META_PREFIX = '%_';
        public const META_SUFFIX = '_%';
        public const PATTERN = '/%_.*?_%/';
        public const SEPARATOR_N = "\n";
        public const SEPARATOR_SEMI_COLON = ';';
        public const EQUAL = '=';

        protected $btTable = 'btJlSearchResults';
        protected $btCacheBlockRecord = true;
        protected $btCacheBlockOutput = false;
        public $result_timing = [];

        /**
         * Unused?
         *
         * @var string[]
         */
        public $reservedParams = ['page=', 'query=', 'search_paths[]=', 'submit=', 'search_paths%5B%5D='];


        public function getBlockTypeName()
        {
            return t('Search++ Results');
        }

        public function getBlockTypeDescription()
        {
            return t('Search Results, show results for searches originating from the Search++ query block, on the current page and from any page.');
        }

        public function getBlockTypeDefaultSet()
        {
            return 'form';
        }


        public function add()
        {
            $this->set('no_result_message', t('There were no results found. Please try another keyword or phrase.'));
            $this->edit();
        }

        public function edit()
        {
            $this->set('pageSelector', Application::getFacadeApplication()->make('helper/form/page_selector'));

            /*
             * Quick check that search has been set up.
             */
            $db = Database::connection();
            $this->set('index_exists', (bool) $db->fetchColumn('select cID from PageSearchIndex limit 1'));
        }

        public function save($data)
        {
            $data['title'] = trim((string) $data['title']);
            $data['no_result_message'] = trim((string) $data['no_result_message']);
            $data['paginate_results'] = (int) ($data['paginate_results'] ?? 0);
            $data['paginate_results_by'] = (int) ($data['paginate_results_by'] ?? 0);
            parent::save($data);
        }

        public function registerViewAssets($outputContent = '')
        {
            $this->addHeaderItem('<style>' . Highlight::get_styles() . '</style>');
        }

        /*
         * Split between view() and do_search().
         * do_search() is a callback and only called when results
         * not from cache. Try and keep any expensive processing
         * in do_cache()
         */
        public function view()
        {
            $page = Page::getCurrentPage();
            if (is_object($page) && !$page->isError()) {
                $cID = $page->getCollectionID();
            }
            $this->set('app', $this->app);
            $pkg = $this->app->make(PackageService::class)->getByHandle('jl_search_plus_plus');
            $this->cfg = $pkg->getFileConfig();
            $this->set('cfg', $this->cfg);

            /*
             * Pull query from parameters. If it is an array, we only want the first one.
             */
            $this->search_query = '';
            if (is_string($this->request->request->get('query'))) {
                $this->search_query = trim($this->request->request->get('query'));
            }
            if (empty($this->search_query) && is_string($this->request->query->get('query'))) {
                $this->search_query = trim($this->request->query->get('query'));
            }
            if (is_array($this->search_query)) {
                $this->search_query = reset($this->search_query);
            }

            $security = new SearchSecurity($this->search_query);
            if (!$security->secuirty_ok()) {
                $this->search_query = '';
            }

            /*
             * Propagate search paths from parameters, or initialise
             * from beneath url
             */
            $this->search_paths = [];
            if (!empty($this->search_query)) {
                if (is_array($this->request->request->get('search_paths'))) {
                    $this->search_paths = $this->request->request->get('search_paths');
                }
                if (empty($this->search_paths) && is_array($this->request->query->get('search_paths'))) {
                    $this->search_paths = $this->request->query->get('search_paths');
                }
            }

            /*
             * default pagination
             * need the info here because it affects cache key
             */
            if (empty($this->paginate_results) && empty($this->paginate_results_by)) {
                $this->paginate_results = true;
                $this->paginate_results_by = 10;
            }

            $this->cache_helper = new CacheHelper($this->search_query, $this->paginate_results_by);
            $this->set('cache_helper', $this->cache_helper);
        }

        /*
         * Restructured this way, so we can do a callback from view instead of all
         * the set() stuff, so facilitating cache in the view
         */
        public function do_search($ajax = false)
        {
            if ($ajax) {
                $this::view();
            }

            $search_times = [];
            $search_times[] = microtime(true);
            $metaArray = [];

            $security = new SearchSecurity($this->search_query);
            if (!$security->secuirty_ok()) {
                $this->search_query = '';
                return;
            }

            if (!empty($this->search_query)) {

                $ext_search = new ExtendedSearch($this->search_query, $this->search_paths);
                if (!empty($this->paginate_results) && !empty($this->paginate_results_by)) {
                    $ext_search->set_pagination($this->paginate_results_by);
                }
                $results = $ext_search->do_search();
                SearchResultLogging::log_search_results($this->search_query, $results, $ext_search->get_max_index_score(), $ext_search->get_last_score());

                $query_times = $ext_search->get_query_timing();
                $pagination = $ext_search->get_pagination();

                $search_in = $this->cfg->get('options.score.search_in');
                $max_index_score = $ext_search->get_max_index_score();
            }

            $search_times[] = microtime(true);

            /*
             * Now build into a result data structure.
             * A similar structure can potentially be built by the autocomplete
             * so it can join in the searching
             */

            $results_data = [];

            /*
             * Title
             */
            if (DebugOutput::confirm_s('show_overall_search_time') && !empty($search_times)) {
                $this->set('search_time', t(
                    '%s[Overall search %.3fs]%s',
                    '<span class="spp_diag_t">',
                    abs(last($search_times) - reset($search_times)),
                    '</span>'
                ));
            }
            if (DebugOutput::confirm_s('show_time') && !empty($query_times)) {
                $this->set('query_time', t(
                    '%s[Queries %s = %.3fs]%s',
                    '<span class="spp_diag_qt">',
                    implode('+', array_map(function ($a) {
                        return sprintf('%.3f', $a);
                    }, $query_times)),
                    array_sum($query_times),
                    '</span>'
                ));
            }

            if (!empty($results) && is_array($results)) {

                $result_times = [];
                $results_data['results'] = [];

                DebugOutput::display_s('highlight', t('Highlight - terms to highlight'), SearchWordNote::get_singleton()->get_all());

                foreach ($results as $r) {
                    $this_result = [];

                    /*
                     * Inside the loop, so we get destructed for each result.
                     */
                    $highlight = new Highlight();

                    /*
                     * Name is always shown, but only highlighted if searched in
                     */
                    $this_result_heading = [];
                    if (is_array($search_in) && in_array('cName', $search_in)) {
                        $this_result_heading[] = $highlight->mark_terms(strip_tags($r->getCollectionName()));
                    } else {
                        $this_result_heading[] = strip_tags($r->getCollectionName());
                    }
                    if (DebugOutput::confirm_s('show_score')) {
                        $this_result_heading[] = t(
                            '%s[%.2f]%s',
                            '<span class="spp_diag_score">',
                            $r->getPageIndexScore(),
                            '</span>'
                        );
                    }
                    if (DebugOutput::confirm_s('show_percentage') && !empty($max_index_score)) {
                        $this_result_heading[] = t(
                            '%s[%.2f%%]%s',
                            '<span class="spp_diag_percent">',
                            min(100, 100 * $r->getPageIndexScore() / $max_index_score),
                            '</span>'
                        );
                    }
                    $this_result['result_heading'] = implode(' ', array_filter($this_result_heading));

                    /*
                     * Description - optionally shown
                     */
                    $this_result[self::RESULTS_CONTENT] = [];
                    if ($r->getCollectionDescription() && is_array($search_in) && in_array('cDescription', $search_in)) {
                        $this_result[self::RESULTS_CONTENT][] = $r->getCollectionDescription();
                    }
                    if ($r->getPageIndexContent() && is_array($search_in) && in_array('content', $search_in)) {
                        $this_result[self::RESULTS_CONTENT][] = $r->getPageIndexContent();
                    }

                    $this_result[self::RESULTS_CONTENT] = array_filter($this_result[self::RESULTS_CONTENT]);
                    $text = '';
                    if (empty($this_result[self::RESULTS_CONTENT])) {
                        unset($this_result[self::RESULTS_CONTENT]);
                    } else {
                        $text = implode(self::SEPARATOR_N, $this_result[self::RESULTS_CONTENT]);
                    }
                    preg_match_all(self::PATTERN, $text, $metaResults);

                    if ($metaResults[0]) {
                        foreach ($metaResults[0] as $result) {
                            // Use the search query again to search the arrays for the keyword, then add to results
                            $found = false;
                            $search_query_items = explode(' ', $this->search_query);
                            foreach ($search_query_items as $search_query_item) {
                                if (strpos(strtolower($result), strtolower($search_query_item)) !== false) {
                                    $found = true;
                                    break;
                                }
                            }

                            if ($found) {
                                $expressItem = [];
                                $result = substr($result, 2, -2); // Removes the %_, _% from beginning and end of string
                                $expressItemMetaArray = explode(self::SEPARATOR_SEMI_COLON, $result);
                                foreach ($expressItemMetaArray as $item) {
                                    $expressItem[substr($item, 0, strpos($item, self::EQUAL))] = substr($item, strpos($item, self::EQUAL) + 1);
                                }

                                if ($expressItem) {
                                    $config = $this->app->make(Config::class);
                                    $param_type = (string) $config->get('lemonbrain_extended_search::extended_search.param_type');

                                    $this_result[self::RESULTS_CONTENT] = $highlight->mark_extended_terms("{$expressItem[self::EXPRESS_TITLE]} {$expressItem[self::EXPRESS_CONTENT]}");
                                    if (isset($expressItem[self::EXPRESS_LINK])) {
                                        $this_result[self::RESULTS_LINK_URL] = $expressItem[self::EXPRESS_LINK];
                                        $this_result[self::RESULTS_LINK_TEXT] = $expressItem[self::EXPRESS_LINK];
                                        $this_result[self::RESULTS_LINK_TARGET] = '_blank';
                                    } else {
                                        $this_result[self::RESULTS_LINK_URL] = "{$r->getCollectionLink()}{$param_type}{$expressItem[self::EXPRESS_HANDLER]}" . self::EQUAL . "{$expressItem[self::EXPRESS_ID]}";
                                        $this_result[self::RESULTS_LINK_TEXT] = "{$r->getCollectionPath()}{$param_type}{$expressItem[self::EXPRESS_HANDLER]}" . self::EQUAL . "{$expressItem[self::EXPRESS_ID]}";
                                        $this_result[self::RESULTS_LINK_TARGET] = '_self';
                                    }

                                    // Accumulate this_result
                                    $results_data[self::RESULTS][] = $this_result;
                                }
                            }
                        }
                    } else {
                        if (array_key_exists(self::RESULTS_CONTENT, $this_result)) {
                            $this_result[self::RESULTS_CONTENT] = $highlight->mark_extended_terms($this_result[self::RESULTS_CONTENT]);
                        }
                        $this_result[self::RESULTS_LINK_URL] = $r->getCollectionLink();
                        $this_result[self::RESULTS_LINK_TEXT] = $r->getCollectionPath();

                        /*
                         * Accumulate this_result
                         */
                        $results_data['results'][] = $this_result;
                        $result_times[] = microtime(true);
                    }
                }

                if (is_object($pagination) && $pagination->haveToPaginate()) {
                    $results_data['pagination'] = $pagination->renderDefaultView();
                }
            }
            $this->set('individual_result_times', $result_times ?? null);

            if ($ajax) {
                return json_encode([
                    'query' => $this->search_query,
                    'results' => key_exists('results', $results_data) ? $results_data['results'] : [],
                ]);
            } else {
                return $results_data;
            }
        }
    }
} else {
    class Controller extends BlockController {}
}
