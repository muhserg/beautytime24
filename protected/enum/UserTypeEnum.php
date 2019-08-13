<?php

interface UserTypeEnum
{
    const CLIENT = 'client';

    const MASTER = 'master';

    const SALON = 'salon';

    //раздатчик буклетов, промоутер, рекламщик
    const PROMOTER = 'promoter';

    //поставщик материалов салону или мастеру
    const PROVIDER = 'provider';

    const ADMIN = 'admin';
}
