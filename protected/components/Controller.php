<?php

use Jenssegers\Agent\Agent;


/**
 * Доработанный класс контроллера
 */
class Controller extends CController
{
    /** @var string the default layout for the controller view. Defaults to '//layouts/column1'. */
    public $layout = '';

    /** @var array context menu items. This property will be assigned to {@link CMenu::items}. */
    public $menu = [];

    /**
     * @var array the breadcrumbs of the current page. The value of this property will
     * be assigned to {@link CBreadcrumbs::links}. Please refer to {@link CBreadcrumbs::links}
     */
    public $breadcrumbs = [];

    /**
     * Ответ контроллера при ajax
     *
     * @param array $data данные ответа
     * @param bool $bShowHeader показывать ли header()
     * @return string
     */
    protected function returnJson($data, $bShowHeader = true)
    {
        if ($bShowHeader === true) {
            header('Content-type: application/json');
        }
        echo json_encode($data);

        Yii::app()->end();
    }

    /**
     * Ответ контроллера
     *
     * @param bool $error есть ли ошибка
     * @param string $message
     * @param bool $bShowHeader показывать ли header()
     * @return string
     */
    protected function response($message, $error = false, $techMessage = '', $bShowHeader = true)
    {
        $this->returnJson([
            'error' => $error,
            'message' => $message,
            'tech_message' => $techMessage,
        ], $bShowHeader);
    }

    /**
     * @param PublicMessageException $e
     * @return string
     */
    protected function renderJsonException(PublicMessageException $e)
    {
        header(
            (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP') . ' 500 Internal Server Error',
            true,
            500
        );
        $out['errorMsg'] = $e->getMessage();
        $this->returnJson($out);
    }

    /**
     * Инициализация статистики и парсинг user-agent'а
     *
     * @param string $action
     * @return bool
     */
    public function beforeAction($action)
    {
        try {
            if (!isset($_SESSION["id"])) {
                StatisticService::getInstance()->createSession();
            }
            StatisticService::getInstance()->createHit();

            if (LOAD_SUMMARY_STATISTIC === true) {
                StatisticService::getInstance()->loadMasterCounts();
                StatisticService::getInstance()->loadClientCounts();
            }

            if (!isset($_SESSION["isMobile"]) || DEV_MODE === true) {
                $agent = new Agent();
                $_SESSION["device"] = $agent->device();
                $_SESSION["isMobile"] = $agent->isMobile();
            }
        } catch (PublicMessageException $e) {
            echo $e->getMessage();
        }

        return parent::beforeAction($action);
    }
}
