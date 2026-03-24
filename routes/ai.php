<?php

use Laravel\Boost\Mcp\Boost;
use Laravel\Mcp\Facades\Mcp;

// Mcp::web();
Mcp::web('/mcp', Boost::class);
