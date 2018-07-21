<?php

namespace TestApp\Auth\AclAdapter;

use TinyAuth\Auth\AclAdapter\AclAdapterInterface;

class CustomAclAdapter implements AclAdapterInterface
{
    /**
     * {@inheritdoc}
     */
    public function getAcl($availableRoles, $tinyConfig)
    {
        return [];
    }
}
