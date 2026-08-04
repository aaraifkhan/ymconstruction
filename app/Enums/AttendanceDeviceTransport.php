<?php

namespace App\Enums;

enum AttendanceDeviceTransport: string
{
    case Unknown = 'unknown';
    case CsvFile = 'csv_file';
    case VendorApi = 'vendor_api';
    case LocalAgent = 'local_agent';
    case PushWebhook = 'push_webhook';
    case TcpPull = 'tcp_pull';
    case Sftp = 'sftp';
    case ZkTecoAdms = 'zkteco_adms';
}
