<?php

namespace SfdcApiSdk\Auth;

interface AuthProviderInterface {
    public function getInstanceUrl();
    public function getAccessToken();
    public function getHttpClient();
}
