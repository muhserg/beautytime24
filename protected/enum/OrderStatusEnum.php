<?php

/** статусы заказов */
interface OrderStatusEnum
{
    const CREATED = 1;

    const SEND_TO_AGREE = 2;

    const AGREE = 3;

    const DONE = 4;

    const ASSESSMENT = 5;

    const OUT_DATE = 6;

    const DELETED = 7;
}
