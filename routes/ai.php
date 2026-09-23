<?php

use App\Mcp\Servers\SolluProjectServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::local('sollu-project', SolluProjectServer::class);
Mcp::web('/mcp/sollu-project', SolluProjectServer::class);
