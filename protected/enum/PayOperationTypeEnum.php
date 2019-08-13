<?php

interface PayOperationTypeEnum
{
    //пополнение баланса
    const ADD_PAYMENT = 'add_payment';

    //оплата абонентской платы за смс оповещение
    const SMS_PAY = 'sms_pay';
}
