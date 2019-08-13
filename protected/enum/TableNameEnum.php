<?php

interface TableNameEnum
{
    const USER = 'user';
    const USER_TYPE = 'user_type';
    const FEEDBACK = 'feedback';

    const CLIENT_PROFILE = 'client_profile';
    const MASTER_PROFILE = 'master_profile';
    const SALON_PROFILE = 'salon_profile';
    const PROMOTER_PROFILE = 'promoter_profile';
    const PROVIDER_PROFILE = 'provider_profile';

    const MASTER_PORTFOLIO = 'master_portfolio';
    const SALON_PORTFOLIO = 'salon_portfolio';

    const DIRECTION = 'direction';
    const SERVICE = 'service';

    const MASTER_PROFILE_DIRECTION = 'master_profile_direction';
    const MASTER_PROFILE_SERVICE = 'master_profile_service';
    const SALON_PROFILE_DIRECTION = 'salon_profile_direction';
    const SALON_PROFILE_SERVICE = 'salon_profile_service';
    const MASTER_ADDITIONAL_SERVICE = 'master_additional_service';
    const SALON_ADDITIONAL_SERVICE = 'salon_additional_service';

    const WORK_SCHEDULE = 'work_schedule';
    const VACANCY = 'vacancy';
    const VACANCY_SCHEDULE = 'vacancy_schedule';

    const ORDER = 'orders';
    const BUF_ORDER = 'buf_order';
    const ORDER_STATUS = 'order_status';
    const BUF_MASTER_ORDER = 'buf_master_order';
    const BUF_SALON_ORDER = 'buf_salon_order';

    const HIT = 'hit';
    const SESSION = 'session';

    const REVIEW = 'review';
    const MESSAGE = 'message';
    const USERS_MESSAGE = 'users_message';
    const NOTIFY = 'notify';
    const PROMOTION = 'promotion';

    const SMS = 'sms';
    const STUDY_VIDEO = 'study_video';

    const PAY_OPERATION_TYPE = 'pay_operation_type';
    const PAY_OPERATION = 'pay_operation';
}
