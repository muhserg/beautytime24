<?php

/**
 * Шаблонизатор twig
 */
class TemplateEngineTwig extends \Twig_Environment
{
    public function __construct()
    {
        $langDir = isset(Yii::app()->session['language']) ? Yii::app()->session['language'] : 'ru';
        $loader = new \Twig_Loader_Filesystem(__DIR__ . '/../templates/' . $langDir . '/');

        $twigParams = [];
        if (CACHE_TWIG === true) {
            $twigParams = [
                'cache' => TWIG_CACHE_DIR,
                //'strict_variables' => true
            ];
        }

        parent::__construct($loader, $twigParams);
    }

    /**
     * Загрузка шаблона
     *
     * @param string
     * @return Twig_TemplateWrapper
     */
    public function load($template)
    {
        try {
            return parent::load($template);
        } catch (Twig_Error_Loader $e) {
            BtLogger::getLogger()->error('Twig template cannot be found.',
                [
                    'template' => $template,
                    'error' => $e,
                ]
            );
            echo $e->getMessage();
            Yii::app()->end();
        } catch (Twig_Error_Runtime $e) {
            BtLogger::getLogger()->error('Twig previously generated cache is corrupted.',
                [
                    'template' => $template,
                    'error' => $e,
                ]
            );
            echo $e->getMessage();
            Yii::app()->end();
        } catch (Twig_Error_Syntax $e) {
            BtLogger::getLogger()->error('Twig error occurred during compilation.',
                [
                    'template' => $template,
                    'error' => $e,
                ]
            );
            echo $e->getMessage();
            Yii::app()->end();
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Twig template not load.',
                [
                    'template' => $template,
                    'error' => $e,
                ]
            );
            echo $e->getMessage();
            Yii::app()->end();
        }
    }

    /**
     * Заполнение переменных для шаблона Twig из сессии
     *
     * @param array $context
     * @return array
     */
    public function fillContextFromSession($context)
    {
        $context = array_merge([
            'showTopHelpLinks' => isset($context['showTopHelpLinks']) ? $context['showTopHelpLinks'] : true,
            'showBrandBtNotice' => isset($context['showBrandBtNotice']) ? $context['showBrandBtNotice'] : true,
            'lang' => (isset(Yii::app()->session['language']) ? Yii::app()->session['language'] : 'ru'),
            'userAuthorizedFlag' => (isset(Yii::app()->session['authorized']) ? Yii::app()->session['authorized'] : false),
            'userId' => Yii::app()->session['user']['id'],
            'userLogin' => Yii::app()->session['user']['login'],
            'userType' => Yii::app()->session['user']['type'],
            'masterCount' => (isset(Yii::app()->session['masterCount']) ? Yii::app()->session['masterCount'] : 0),
            'clientCount' => (isset(Yii::app()->session['clientCount']) ? Yii::app()->session['clientCount'] : 0),

            'userIsMaster' => (Yii::app()->session['user']['type'] === UserTypeEnum::MASTER),
            'userIsClient' => (Yii::app()->session['user']['type'] === UserTypeEnum::CLIENT),
            'userIsSalon' => (Yii::app()->session['user']['type'] === UserTypeEnum::SALON),

            'salonType' => UserTypeEnum::SALON,

            //мобильная верстка
            'isMobile' => (isset(Yii::app()->session['isMobile']) ? Yii::app()->session['isMobile'] : false),
            'device' => (isset(Yii::app()->session['device']) ? Yii::app()->session['device'] : false),
        ], $context);

        $isM = ($context['isMobile'] === true);
        $context = array_merge([
            'showRow' => ($isM ? '' : ' row '),
            'showCol' => ($isM ? '' : ' col '),
            'showColSm6' => ($isM ? '' : ' col-sm-6 '),
            'mobileW_100_75' => ($isM ? ' w-100 ' : ' w-75 '),
            'mobileW_100_25' => ($isM ? ' w-100 ' : ' w-25 '),
            'isMaxW500' => ($isM ? '' : ' max-w-500 '),
            'mobileHide' => ($isM ? ' hide ' : ''),
            'faSize' => ($isM ? ' fa-2x ' : ' fa-lg '),
            'controlLg' => ($isM ? ' form-control-xl ' : ''),
            'lbLg' => ($isM ? ' lb-lg ' : ''),
            'lbLg_pt2' => ($isM ? ' lb-lg pt-2 ' : ''),
            'pt2' => ($isM ? ' pt-2 ' : ''),
            'pl2' => ($isM ? ' pl-2 ' : ' '),
            'pl2_pl4' => ($isM ? ' pl-4 ' : ' pl-2 '),
            'pl0' => ($isM ? ' pl-0 ' : ''),
            'col_xl_10' => ($isM ? ' ' : ' col-xl-10 '),
            'lbLg_pl4_pl2' => ($isM ? ' lb-lg pl-4 ' : ' pl-2 '),
            'b_xl_chbx' => ($isM ? ' big-xl-checkbox ' : ' big-checkbox '),
            'hLg' => ($isM ? ' h-lg ' : ''),
            'smallLg' => ($isM ? ' lb-small-lg ' : ''),
            'lbHeight' => ($isM ? 'height: 1.36em;' : 'height: 1.22em;'),
            'minW_400_650' => ($isM ? ' min-w-650 ' : ' min-w-400 '),
            'minW_300_500' => ($isM ? ' min-w-500 ' : ' min-w-300 '),
            'pb07em' => ($isM ? ' pb-07-em ' : ''),
            'lbProfile' => ($isM ? ' pl-4 ' : ' col-sm-2 px-0 profile-big-label text-right '),
            'colCol_4' => ($isM ? ' col ' : ' col-4 '),
            'colCol_2' => ($isM ? ' col ' : ' col-2 '),
            'navIcon' => ($isM ? ' big-icon ' : ' meduim-icon '),
            'mobileW_w500_100' => ($isM ? ' w-100 ' : ' curr-w-500 '),
            'mtMinus' => ($isM ? ' mt-minus-2 ' : ' mt-minus-1 '),
            'mb4_5' => ($isM ? ' mb-5 ' : ' mb-4 '),
        ], $context);

        return $context;
    }

    /**
     * Заполнение переменных для шаблона Twig из констант
     *
     * @param $context
     * @return array
     */
    public function fillContextToConstant($context)
    {
        $context = array_merge([
            'currYear' => date('Y'),
            'currDate' => date('d.m.Y'),
            'siteName' => SITE_NAME,
            'siteNameRus' => SITE_NAME_RUS,
            'siteHost' => SITE_HOST,
            'loadSummaryStatistic' => LOAD_SUMMARY_STATISTIC,
            'pageRefreshTime' => PAGE_RESRESH_TIME,
            'limitUploadFileSize' => LIMIT_UPLOAD_FILE_SIZE,
        ], $context);

        $context['emailInfoImg'] = ImageHelper::createImageFromString(
            'info@' . SITE_NAME . '.ru',
            $context['isMobile']
        );
        $context['emailPrImg'] = ImageHelper::createImageFromString(
            'pr@' . SITE_NAME . '.ru',
            $context['isMobile']
        );

        return $context;
    }

    /**
     * Преобразование переменных для шаблона Twig с учетом безопасности
     *
     * @param $context
     * @return array
     */
    public function fillContextCalcParams($context)
    {
        return $context;
    }

    /**
     * Отображение шаблона
     *
     * @param string $template название шаблона
     * @param array $context переменные для отображения шаблона
     * @return string
     */
    public function render($template, array $context = [])
    {
        $context = $this->fillContextFromSession($context);
        $context = $this->fillContextToConstant($context);
        $context = $this->fillContextCalcParams($context);
        $headError = "Не удалось отобразить шаблон: ";

        try {
            return parent::render($template, $context);
        } catch (Twig_Error_Loader $e) {
            BtLogger::getLogger()->error('Twig template cannot be found.',
                [
                    'template' => $template,
                    'error' => $e,
                ]
            );
            echo $headError . $e->getMessage();
            Yii::app()->end();
        } catch (Twig_Error_Runtime $e) {
            BtLogger::getLogger()->error('Twig previously generated cache is corrupted.',
                [
                    'template' => $template,
                    'error' => $e,
                ]
            );
            echo $headError . $e->getMessage();
            Yii::app()->end();
        } catch (Twig_Error_Syntax $e) {
            BtLogger::getLogger()->error('Twig error occurred during compilation.',
                [
                    'template' => $template,
                    'error' => $e,
                ]
            );
            echo $headError . $e->getMessage();
            Yii::app()->end();
        } catch (Exception $e) {
            BtLogger::getLogger()->error('Twig template not load.',
                [
                    'template' => $template,
                    'error' => $e,
                ]
            );
            echo $headError . $e->getMessage();
            Yii::app()->end();
        }
    }
}

