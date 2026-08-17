<?php

namespace App\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;

class GenerateWorkflowGuidePdfCommand extends Command
{
    protected $signature = 'generate:workflow-guide-pdf {--output= : Custom output path}';

    protected $description = 'Generate a high-density, professional Department Workflow & User Guide PDF.';

    public function handle(): int
    {
        $outputPath = $this->option('output') ?: public_path('downloads/Department_Workflow_User_Guide.pdf');

        $directory = dirname($outputPath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $this->info('Generating Workflow Guide PDF document...');

        $html = $this->getHtmlContent();

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('a4', 'portrait');
        $pdf->setWarnings(false);
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'Helvetica',
            'dpi' => 150,
        ]);

        $pdf->save($outputPath);

        $this->info("PDF successfully generated at: {$outputPath}");
        $this->info('File size: '.round(filesize($outputPath) / 1024, 2).' KB');

        return self::SUCCESS;
    }

    private function getHtmlContent(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Department Hierarchy, Task Workflows & Daily Reporting System - Complete User Guide</title>
<style>
    @page {
        margin: 10mm 12mm 10mm 12mm;
    }
    body {
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        color: #1e293b;
        font-size: 8.5pt;
        line-height: 1.35;
        margin: 0;
        padding: 0;
    }
    h1, h2, h3, h4, h5, p {
        margin: 0;
        padding: 0;
    }
    .header-banner {
        background-color: #0f172a;
        color: #ffffff;
        padding: 14px 18px;
        border-radius: 6px;
        margin-bottom: 12px;
    }
    .header-title {
        font-size: 14pt;
        font-weight: bold;
        color: #f8fafc;
        letter-spacing: 0.3px;
    }
    .header-subtitle {
        font-size: 8.5pt;
        color: #94a3b8;
        margin-top: 3px;
    }
    .header-meta {
        margin-top: 6px;
        font-size: 7.5pt;
        color: #cbd5e1;
        border-top: 1px solid #334155;
        padding-top: 4px;
    }
    .section-title {
        font-size: 11pt;
        font-weight: bold;
        color: #0f172a;
        border-left: 4px solid #2563eb;
        padding-left: 6px;
        margin-top: 10px;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }
    .section-subtitle {
        font-size: 8pt;
        color: #64748b;
        margin-bottom: 6px;
    }
    .card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 5px;
        padding: 8px 10px;
        margin-bottom: 8px;
        page-break-inside: avoid;
    }
    .card-dark {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
    }
    .card-title {
        font-size: 9.5pt;
        font-weight: bold;
        color: #1e293b;
        margin-bottom: 4px;
        display: block;
    }
    .badge {
        display: inline-block;
        padding: 1.5px 5px;
        font-size: 6.5pt;
        font-weight: bold;
        border-radius: 3px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .badge-primary { background: #dbeafe; color: #1e40af; }
    .badge-success { background: #d1fae5; color: #065f46; }
    .badge-warning { background: #fef3c7; color: #92400e; }
    .badge-danger { background: #fee2e2; color: #991b1b; }
    .badge-dark { background: #e2e8f0; color: #334155; }
    .badge-purple { background: #ede9fe; color: #5b21b6; }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 4px;
        margin-bottom: 6px;
        font-size: 7.5pt;
    }
    th {
        background-color: #f1f5f9;
        color: #334155;
        font-weight: bold;
        text-align: left;
        padding: 4px 6px;
        border: 1px solid #cbd5e1;
        text-transform: uppercase;
        font-size: 7pt;
    }
    td {
        padding: 4px 6px;
        border: 1px solid #e2e8f0;
        vertical-align: top;
    }
    tr:nth-child(even) td {
        background-color: #f8fafc;
    }
    .step-box {
        border-left: 2.5px solid #2563eb;
        background: #f8fafc;
        padding: 5px 8px;
        margin-bottom: 5px;
        border-radius: 0 4px 4px 0;
        page-break-inside: avoid;
    }
    .step-num {
        font-weight: bold;
        color: #2563eb;
        font-size: 8pt;
    }
    .step-text {
        font-size: 7.8pt;
        color: #334155;
        margin-top: 1px;
    }
    .grid-2 {
        width: 100%;
        margin-bottom: 4px;
    }
    .grid-2 td {
        border: none;
        padding: 0 4px;
        background: transparent !important;
    }
    .workflow-flow {
        background: #f1f5f9;
        border-radius: 4px;
        padding: 6px 10px;
        margin: 5px 0;
        text-align: center;
        font-weight: bold;
        font-size: 7.5pt;
        color: #1e293b;
        border: 1px dashed #94a3b8;
    }
    .flow-arrow {
        color: #2563eb;
        padding: 0 4px;
    }
    .highlight-box {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 4px;
        padding: 5px 8px;
        margin: 4px 0;
        font-size: 7.8pt;
        color: #1e3a8a;
    }
    .footer {
        text-align: center;
        font-size: 7pt;
        color: #94a3b8;
        border-top: 1px solid #e2e8f0;
        padding-top: 4px;
        margin-top: 10px;
    }
    .page-break {
        page-break-after: always;
    }
</style>
</head>
<body>

<!-- PAGE 1: SYSTEM ARCHITECTURE & DEPARTMENT HEAD WORKFLOW -->
<div class="header-banner">
    <table style="border:none; margin:0; width:100%;">
        <tr>
            <td style="border:none; padding:0; background:transparent;">
                <div class="header-title">DEPARTMENT HIERARCHY, TASK WORKFLOWS & DAILY REPORTING SYSTEM</div>
                <div class="header-subtitle">Comprehensive Standard Operating Procedure (SOP) & Step-by-Step UI Navigation Guide</div>
            </td>
            <td style="border:none; padding:0; text-align:right; background:transparent; width:180px;">
                <span class="badge badge-primary" style="font-size:8pt; padding:4px 8px;">Native Filament v5</span>
                <div class="header-meta">Platform: Laravel 13 • Livewire 4</div>
            </td>
        </tr>
    </table>
</div>

<!-- ARCHITECTURE OVERVIEW -->
<div class="card card-dark">
    <div class="card-title">1. Global Architectural Foundation</div>
    <div class="workflow-flow">
        Company Tenant <span class="flow-arrow">&rarr;</span> 
        Department <span class="flow-arrow">&rarr;</span> 
        Department Teams <span class="flow-arrow">&rarr;</span> 
        Team Leads (L2) <span class="flow-arrow">&rarr;</span> 
        Team Members (L3) <span class="flow-arrow">&rarr;</span> 
        Specialized Tasks <span class="flow-arrow">&rarr;</span> 
        Daily Reports (6 PM) <span class="flow-arrow">&rarr;</span> 
        2-Tier Approvals <span class="flow-arrow">&rarr;</span> 
        Productivity Scorecard
    </div>
    <p style="font-size:7.8pt; color:#475569; margin-top:2px;">
        Designed strictly around organizational accountability: Department Heads have global visibility and final sign-off authority; Team Leads supervise team-scoped task execution and first-tier reviews; Team Members execute in specialized modules and file mandatory 6:00 PM daily check-ins.
    </p>
</div>

<!-- ROLE 1: DEPARTMENT HEAD WORKFLOW -->
<div class="section-title">2. Department Head (You / Admin) — Complete Navigation Flow</div>
<div class="section-subtitle">Global supervision, Team creation, Master Task Assignment, Final Sign-off, and Daily Matrix Auditing</div>

<table class="grid-2">
    <tr>
        <td style="width:50%;">
            <div class="step-box">
                <div class="step-num">STEP 1: Accessing the Operations Command Center</div>
                <div class="step-text">
                    <strong>Sidebar Navigation:</strong> Click on <code>Department Operations &rarr; Head Operations Dashboard</code>.<br>
                    <strong>What You See:</strong> 4 Top KPI Stat Cards (Total Staff, Daily Reports Submitted vs Pending, Active Tasks in Progress, Overdue Tasks, Tasks Awaiting Your Final Approval).
                </div>
            </div>
            <div class="step-box">
                <div class="step-num">STEP 2: Managing Department Teams & Team Leads</div>
                <div class="step-text">
                    <strong>Sidebar Navigation:</strong> Click on <code>Department Operations &rarr; Department Teams</code>.<br>
                    <strong>Creating a Team:</strong> Click <em>"+ New Department Team"</em> button &rarr; Select Department &rarr; Enter Team Name (e.g. "Social Media Team") &rarr; Select Team Type (Social Media, Graphic Design, Video, Web Dev, Sales) &rarr; Assign Team Lead &rarr; Save.
                </div>
            </div>
            <div class="step-box">
                <div class="step-num">STEP 3: Assigning & Delegating Master Tasks</div>
                <div class="step-text">
                    <strong>Sidebar Navigation:</strong> Click on <code>Department Operations &rarr; Tasks & Delegations</code>.<br>
                    <strong>Creating Task:</strong> Click <em>"+ New Task"</em> &rarr; Enter Title, Priority (Low/Medium/High/Urgent), Team, Assignee, Estimated Hours, Deadline Date &rarr; Dynamic workflow fields automatically appear &rarr; Save.
                </div>
            </div>
        </td>
        <td style="width:50%;">
            <div class="step-box">
                <div class="step-num">STEP 4: Level-2 Final Approval Queue (1-Click Action)</div>
                <div class="step-text">
                    <strong>Location:</strong> Directly on <code>Head Operations Dashboard</code> or <code>Tasks Table (Tab: Awaiting Head Approval)</code>.<br>
                    <strong>How to Approve:</strong> Click green <strong>"Approve & Complete"</strong> button on task row &rarr; Enter optional sign-off remarks &rarr; Submit. Status becomes <code>Completed</code> and turnaround time is recorded.<br>
                    <strong>How to Reject/Revise:</strong> Click red <strong>"Request Revision"</strong> &rarr; Enter revision instructions &rarr; Sent back to assignee.
                </div>
            </div>
            <div class="step-box">
                <div class="step-num">STEP 5: Real-Time 6:00 PM Daily Attendance & Work Matrix</div>
                <div class="step-text">
                    <strong>Sidebar Navigation:</strong> Click on <code>Department Operations &rarr; Daily Attendance & Work Matrix</code>.<br>
                    <strong>Features:</strong> Select Date & Filter by Team &rarr; Instant Color Coded Status:<br>
                    • <span class="badge badge-success">🟢 On-Time</span> Submitted &le; 6:00 PM<br>
                    • <span class="badge badge-warning">🟠 Late</span> Submitted after 6:00 PM<br>
                    • <span class="badge badge-danger">🔴 Missing</span> Not submitted after 6:00 PM cutoff<br>
                    Click <em>"View Report &rarr;"</em> to inspect full deliverable details.
                </div>
            </div>
            <div class="step-box">
                <div class="step-num">STEP 6: Evaluating Employee Performance Scorecards</div>
                <div class="step-text">
                    <strong>Sidebar Navigation:</strong> Click on <code>Department Operations &rarr; Performance & Productivity</code>.<br>
                    <strong>Features:</strong> Select Employee & Date Range &rarr; Instant 0–100 composite score, Turnaround Hours, Completion Rate, Revision Penalty, On-Time Submission %.
                </div>
            </div>
        </td>
    </tr>
</table>

<!-- ROLE SUMMARY TABLE -->
<div class="card">
    <div class="card-title">Department Head Action Summary</div>
    <table>
        <thead>
            <tr>
                <th style="width:25%;">Action Goal</th>
                <th style="width:30%;">Sidebar Menu Path</th>
                <th style="width:25%;">Key Button / UI Element</th>
                <th style="width:20%;">Resulting State</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Create New Team</strong></td>
                <td>Department Operations &rarr; Department Teams</td>
                <td><code>+ New Department Team</code></td>
                <td>Active Team with Lead</td>
            </tr>
            <tr>
                <td><strong>Assign Master Task</strong></td>
                <td>Department Operations &rarr; Tasks & Delegations</td>
                <td><code>+ New Task</code></td>
                <td><code>TSK-YYYY-XXXXX</code> (Not Started)</td>
            </tr>
            <tr>
                <td><strong>Final Sign-Off / Approval</strong></td>
                <td>Head Dashboard &rarr; Pending Queue</td>
                <td><code>Approve & Complete</code></td>
                <td>Status: <code>Completed</code></td>
            </tr>
            <tr>
                <td><strong>Audit 6 PM Submissions</strong></td>
                <td>Department Operations &rarr; Daily Work Matrix</td>
                <td>Date / Team Filter Bar</td>
                <td>🟢 / 🟠 / 🔴 Real-Time Grid</td>
            </tr>
            <tr>
                <td><strong>Review Productivity Index</strong></td>
                <td>Department Operations &rarr; Performance</td>
                <td>Employee Selector Dropdown</td>
                <td>0–100 Scorecard & Turnaround</td>
            </tr>
        </tbody>
    </table>
</div>

<div class="footer">YM Construction ERP • Department Workflow Standard Operating Procedure (SOP) • Page 1 of 4</div>

<div class="page-break"></div>

<!-- PAGE 2: TEAM LEAD & TEAM MEMBER WORKFLOWS -->
<div class="header-banner">
    <div class="header-title">TEAM LEAD & TEAM MEMBER WORKFLOWS</div>
    <div class="header-subtitle">Supervisory Review Chain, Daily Task Execution & 6:00 PM Work Reporting Protocols</div>
</div>

<!-- ROLE 2: TEAM LEAD WORKFLOW -->
<div class="section-title">3. Team Lead Workflow — Delegation & Level-1 Quality Review</div>
<div class="section-subtitle">Supervises assigned team members, sets deadlines, conducts Level-1 quality reviews, and requests revisions</div>

<table class="grid-2">
    <tr>
        <td style="width:50%;">
            <div class="step-box">
                <div class="step-num">STEP 1: Monitoring Team Workspace</div>
                <div class="step-text">
                    <strong>Sidebar Navigation:</strong> Click on <code>Department Operations &rarr; Tasks & Delegations</code>.<br>
                    <strong>Filter View:</strong> Use Tab <em>"My Team Tasks"</em> or Filter by your Team.<br>
                    <strong>Visibility:</strong> Team Leads see all tasks belonging to their team members with real-time progress % and deadline tags.
                </div>
            </div>
            <div class="step-box">
                <div class="step-num">STEP 2: Delegating Tasks to Team Members</div>
                <div class="step-text">
                    <strong>Action:</strong> Click <em>"+ New Task"</em> button.<br>
                    <strong>Form Fields:</strong><br>
                    1. <strong>Task Title & Priority:</strong> High / Urgent for critical client deadlines.<br>
                    2. <strong>Assignee:</strong> Select team member from dropdown.<br>
                    3. <strong>Estimated Hours & Deadline:</strong> Specify expected turnaround.<br>
                    4. <strong>Specialized Specs:</strong> Fill creative brief / dev requirements.
                </div>
            </div>
        </td>
        <td style="width:50%;">
            <div class="step-box">
                <div class="step-num">STEP 3: Level-1 Review & Deliverable Inspection</div>
                <div class="step-text">
                    <strong>When Member Submits Work:</strong> Task status changes to <span class="badge badge-warning">Submitted</span>.<br>
                    <strong>How to Inspect:</strong> Open Task &rarr; Review Attached files / Google Drive / Figma / Loom links in <em>Task Attachments</em> relation manager.<br>
                    <strong>If Approved:</strong> Click <strong>"Review & Pass to Head"</strong> &rarr; Level-1 timestamp and Lead ID recorded &rarr; Task sent to Department Head.<br>
                    <strong>If Changes Needed:</strong> Click <strong>"Request Revision"</strong> &rarr; Type specific feedback &rarr; Revision counter increments & status becomes <span class="badge badge-danger">Revision Required</span>.
                </div>
            </div>
            <div class="step-box">
                <div class="step-num">STEP 4: Reviewing Daily Work Reports</div>
                <div class="step-text">
                    <strong>Sidebar Navigation:</strong> Click on <code>Department Operations &rarr; Daily Work Reports</code>.<br>
                    <strong>Action:</strong> Open member report &rarr; Click <strong>"Review & Acknowledge"</strong> &rarr; Add remarks (e.g. "Good progress on wireframes").
                </div>
            </div>
        </td>
    </tr>
</table>

<!-- ROLE 3: TEAM MEMBER WORKFLOW -->
<div class="section-title" style="margin-top:12px;">4. Team Member (Employee) Workflow — Daily Work & 6:00 PM Reporting</div>
<div class="section-subtitle">Personal task queue, updating progress, uploading deliverables, and filing mandatory 6:00 PM check-in</div>

<table class="grid-2">
    <tr>
        <td style="width:50%;">
            <div class="step-box">
                <div class="step-num">STEP 1: Morning Check-in & Viewing Assigned Tasks</div>
                <div class="step-text">
                    <strong>Sidebar Navigation:</strong> Click on <code>Department Operations &rarr; Tasks & Delegations</code>.<br>
                    <strong>Default Tab:</strong> <em>"My Assigned Tasks"</em>.<br>
                    <strong>Action:</strong> Open today's task &rarr; Change status from <code>Not Started</code> to <code>In Progress</code>.
                </div>
            </div>
            <div class="step-box">
                <div class="step-num">STEP 2: Attaching Deliverables & Submitting Task</div>
                <div class="step-text">
                    <strong>When Work is Ready:</strong><br>
                    1. Scroll to <strong>Task Attachments / Links</strong> tab.<br>
                    2. Click <em>"Add Deliverable Link"</em> &rarr; Paste Google Drive, Figma, GitHub PR, Canva, or Loom URL.<br>
                    3. Click row action <strong>"Submit Deliverable"</strong> &rarr; Enter submission remarks &rarr; Status becomes <span class="badge badge-warning">Submitted</span> (Progress 100%).
                </div>
            </div>
        </td>
        <td style="width:50%;">
            <div class="step-box">
                <div class="step-num">STEP 3: Handling Revision Requests</div>
                <div class="step-text">
                    <strong>If Lead/Head Requests Revision:</strong><br>
                    • Task status shows <span class="badge badge-danger">Revision Required</span>.<br>
                    • Open task &rarr; Check <em>Task Revisions</em> tab to read exact feedback.<br>
                    • Update files/links & click <strong>"Submit Deliverable"</strong> again.
                </div>
            </div>
            <div class="step-box">
                <div class="step-num">STEP 4: Mandatory 6:00 PM Daily Report Submission</div>
                <div class="step-text">
                    <strong>Sidebar Navigation:</strong> Click on <code>Department Operations &rarr; Daily Work Reports</code>.<br>
                    <strong>Action:</strong> Click <em>"+ Submit Daily Report"</em> button.<br>
                    <strong>Auto-Filled Data:</strong> Employee Name, Code, Department, Team.<br>
                    <strong>Task Items Repeater:</strong> Click <em>"+ Add Task Worked"</em> for each item &rarr; Select Task Title, Hours Spent, Deliverable Summary, Work Links, and Blockers.<br>
                    <strong>Sales Team Detail (If Sales):</strong> Enter Calls, Meetings, Proposals, Deals Closed, Revenue.<br>
                    <strong>Cutoff Rule:</strong> Submitting &le; 6:00 PM = <span class="badge badge-success">🟢 On-Time</span>. Submitting > 6:00 PM = <span class="badge badge-warning">🟠 Late</span>.
                </div>
            </div>
        </td>
    </tr>
</table>

<!-- 2-TIER APPROVAL LIFECYCLE DIAGRAM -->
<div class="card card-dark" style="margin-top:6px;">
    <div class="card-title">2-Tier Quality Approval Protocol</div>
    <table>
        <thead>
            <tr>
                <th style="width:20%;">Stage</th>
                <th style="width:25%;">Actor</th>
                <th style="width:25%;">Permitted Actions</th>
                <th style="width:30%;">Next Destination</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>1. Submission</strong></td>
                <td>Team Member (Assignee)</td>
                <td>Upload files, paste URLs, click <code>Submit Deliverable</code></td>
                <td>Task moves to Team Lead Review Queue</td>
            </tr>
            <tr>
                <td><strong>2. Level-1 Review</strong></td>
                <td>Team Lead</td>
                <td>• <code>Review & Pass to Head</code><br>• <code>Request Revision</code></td>
                <td>Passes to Department Head OR Returns to Member</td>
            </tr>
            <tr>
                <td><strong>3. Level-2 Final Approval</strong></td>
                <td>Department Head</td>
                <td>• <code>Approve & Complete</code><br>• <code>Request Revision</code></td>
                <td>Marked <code>Completed</code> (100% Turnaround Clock Stops)</td>
            </tr>
        </tbody>
    </table>
</div>

<div class="footer">YM Construction ERP • Department Workflow Standard Operating Procedure (SOP) • Page 2 of 4</div>

<div class="page-break"></div>

<!-- PAGE 3: SPECIALIZED TEAM WORKFLOWS -->
<div class="header-banner">
    <div class="header-title">SPECIALIZED ROLE & TEAM WORKFLOWS</div>
    <div class="header-subtitle">Detailed Operating Procedures for Social Media, Design, Video, Web Dev & Sales</div>
</div>

<div class="section-title">5. Specialized Workflow Specs & Data Requirements</div>
<div class="section-subtitle">Each specialized team possesses tailored form fields and tracking schemas within Tasks and Daily Reports</div>

<!-- SOCIAL MEDIA & GRAPHIC DESIGN -->
<table class="grid-2">
    <tr>
        <td style="width:50%;">
            <div class="card">
                <div class="card-title"><span class="badge badge-primary">Team 1</span> Social Media Team Workflow</div>
                <div class="step-text">
                    <strong>Form Location:</strong> Inside Task Form &rarr; <em>"Social Media Content Details"</em> (visible when Social Media Team selected).<br>
                    <strong>Input Fields:</strong><br>
                    • <strong>Platforms:</strong> Multi-select (Facebook, Instagram, LinkedIn, TikTok, X, YouTube).<br>
                    • <strong>Content Type:</strong> Static Post, Reel / Short, Carousel, Story, Ad Creative.<br>
                    • <strong>Workflow Stage (8-Steps):</strong> Idea &rarr; Brief &rarr; Copywriting &rarr; Design &rarr; Video Editing &rarr; Client Approval &rarr; Scheduled &rarr; Published.<br>
                    • <strong>Post Caption & Hashtags:</strong> Full text copy & keyword tag bank.<br>
                    • <strong>Target Publishing DateTime:</strong> Scheduled post date/time.<br>
                    • <strong>Cross-Team Delegation:</strong> Assign Designer & Video Editor directly.
                </div>
            </div>
        </td>
        <td style="width:50%;">
            <div class="card">
                <div class="card-title"><span class="badge badge-purple">Team 2</span> Graphic Designer Workflow</div>
                <div class="step-text">
                    <strong>Form Location:</strong> Inside Task Form &rarr; <em>"Graphic Design Specifications"</em>.<br>
                    <strong>Input Fields:</strong><br>
                    • <strong>Design Type:</strong> Social Media Post, Banner / Billboard, Brochure / Flyer, Logo / Branding, UI / Web Design, Presentation Deck.<br>
                    • <strong>Dimensions & Aspect Ratio:</strong> e.g. 1080x1080 (1:1), 1080x1920 (9:16), 1200x628 (1.91:1), Print (A4 / 300DPI).<br>
                    • <strong>Brand / Client Context:</strong> Style guidelines & color palette.<br>
                    • <strong>Design Brief & Copy:</strong> Required headlines and body text.<br>
                    • <strong>Reference Links:</strong> Pinterest / Behance / Competitor links.<br>
                    • <strong>Deliverables:</strong> Source File URL (Figma / PSD / AI) + Preview Image.
                </div>
            </div>
        </td>
    </tr>
</table>

<!-- VIDEO PRODUCTION & WEB DEVELOPMENT -->
<table class="grid-2" style="margin-top:6px;">
    <tr>
        <td style="width:50%;">
            <div class="card">
                <div class="card-title"><span class="badge badge-danger">Team 3</span> Video Production Workflow</div>
                <div class="step-text">
                    <strong>Form Location:</strong> Inside Task Form &rarr; <em>"Video Production Details"</em>.<br>
                    <strong>Input Fields:</strong><br>
                    • <strong>Video Type:</strong> Reel / Short, Promo / Commercial, Site Walkthrough, Corporate Video, YouTube Longform, Podcast Cut.<br>
                    • <strong>Target Duration:</strong> Seconds or Minutes (e.g. 30s, 60s, 3m).<br>
                    • <strong>Aspect Ratio:</strong> 9:16 (Vertical) / 16:9 (Horizontal) / 1:1 (Square).<br>
                    • <strong>Raw Footage URL:</strong> Cloud / Google Drive / Dropbox link.<br>
                    • <strong>Script & Voiceover Notes:</strong> Timed script & narration notes.<br>
                    • <strong>Editing Instructions:</strong> B-roll pace, subtitles, SFX, music tone.<br>
                    • <strong>Video Links:</strong> Draft V1/V2 URL (Frame.io/Drive) + Final Video URL.
                </div>
            </div>
        </td>
        <td style="width:50%;">
            <div class="card">
                <div class="card-title"><span class="badge badge-success">Team 4</span> Web Developer Workflow</div>
                <div class="step-text">
                    <strong>Form Location:</strong> Inside Task Form &rarr; <em>"Web Development & Technical Details"</em>.<br>
                    <strong>Input Fields:</strong><br>
                    • <strong>Task Type:</strong> New Feature, Bug Fix, UI/UX Improvement, API Integration, Performance Optimization, Database / Migration.<br>
                    • <strong>Technical Requirement / Spec:</strong> Functional description.<br>
                    • <strong>GitHub PR / Commit URL:</strong> Branch or Pull Request reference.<br>
                    • <strong>Staging / Test URL:</strong> Client / QA preview environment link.<br>
                    • <strong>Live / Production URL:</strong> Deployed live website link.<br>
                    • <strong>QA Testing Status:</strong> Untested &rarr; In Testing &rarr; Passed QA &rarr; Failed.<br>
                    • <strong>Bug Count:</strong> Open bugs tracking.
                </div>
            </div>
        </td>
    </tr>
</table>

<!-- SALES & CRM TEAM -->
<div class="card" style="margin-top:6px;">
    <div class="card-title"><span class="badge badge-warning">Team 5</span> Sales & CRM Daily Outreach Workflow</div>
    <table style="margin-top:4px;">
        <thead>
            <tr>
                <th style="width:25%;">Outreach Metric</th>
                <th style="width:40%;">Description & Daily Reporting Field</th>
                <th style="width:35%;">Impact on Performance Scorecard</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Leads Received & Contacted</strong></td>
                <td>New inquiries assigned from digital campaigns + first contact attempts</td>
                <td>Measures speed-to-lead response rate</td>
            </tr>
            <tr>
                <td><strong>Calls & WhatsApp Messages</strong></td>
                <td>Total outbound telephone conversations + WhatsApp follow-ups made</td>
                <td>Direct measure of daily outreach volume</td>
            </tr>
            <tr>
                <td><strong>Meetings & Site Visits Booked</strong></td>
                <td>Client appointments scheduled for project inspection / office briefing</td>
                <td>High-weight KPI conversion indicator</td>
            </tr>
            <tr>
                <td><strong>Proposals Sent & Deals Closed</strong></td>
                <td>Formal quotations dispatched + finalized signed contracts</td>
                <td>Direct pipeline revenue generation metric</td>
            </tr>
            <tr>
                <td><strong>Revenue Generated & Lost Reasons</strong></td>
                <td>PKR monetary value booked + categorized root causes for lost leads</td>
                <td>Monthly sales attribution & objection analysis</td>
            </tr>
        </tbody>
    </table>
</div>

<div class="footer">YM Construction ERP • Department Workflow Standard Operating Procedure (SOP) • Page 3 of 4</div>

<div class="page-break"></div>

<!-- PAGE 4: 6:00 PM CUTOFF, NOTIFICATIONS & AUDITING -->
<div class="header-banner">
    <div class="header-title">DEADLINE PROTOCOL, NOTIFICATIONS & AUDITING</div>
    <div class="header-subtitle">6:00 PM Reporting Cutoff Enforcement, CLI Automation & Composite Performance Calculation</div>
</div>

<!-- 6:00 PM CUTOFF LOGIC -->
<div class="section-title">6. Mandatory 6:00 PM Daily Report Cutoff Rules</div>
<div class="section-subtitle">Automatic timestamping and color-coded status assignment on every report submission</div>

<table class="grid-2">
    <tr>
        <td style="width:50%;">
            <div class="card">
                <div class="card-title">Deadline Categorization Rules</div>
                <div style="margin-top:4px;">
                    <div style="margin-bottom:6px;">
                        <span class="badge badge-success" style="font-size:7.5pt;">🟢 On-Time (&le; 6:00 PM)</span><br>
                        <span style="font-size:7.5pt; color:#334155;">Submitted anytime on or before 18:00:00. Boosts employee reliability score.</span>
                    </div>
                    <div style="margin-bottom:6px;">
                        <span class="badge badge-warning" style="font-size:7.5pt;">🟠 Late Submission (> 6:00 PM)</span><br>
                        <span style="font-size:7.5pt; color:#334155;">Submitted after 18:00:00. Flags timestamp in orange; slight penalty on reliability score.</span>
                    </div>
                    <div>
                        <span class="badge badge-danger" style="font-size:7.5pt;">🔴 Missing Report (Unsubmitted)</span><br>
                        <span style="font-size:7.5pt; color:#334155;">No submission recorded for active employee on that date. Triggers automated alerts.</span>
                    </div>
                </div>
            </div>
        </td>
        <td style="width:50%;">
            <div class="card">
                <div class="card-title">Automated Notification CLI Command</div>
                <div class="highlight-box">
                    <strong>Cron / Console Command:</strong><br>
                    <code>php artisan work-reports:check-deadline</code>
                </div>
                <div style="font-size:7.5pt; color:#475569; margin-top:4px;">
                    <strong>Execution Logic:</strong><br>
                    1. Scans all active employees in each company.<br>
                    2. Compares active staff against today's submitted <code>daily_work_reports</code>.<br>
                    3. Broadcasts real-time in-app Filament database notifications to Department Heads and Team Leads with list of missing staff.<br>
                    4. Logs incident in system audit trail.
                </div>
            </td>
    </tr>
</table>

<!-- PRODUCTIVITY SCORECARD FORMULA -->
<div class="section-title" style="margin-top:12px;">7. Composite Employee Productivity Scorecard (0–100 Formula)</div>
<div class="section-subtitle">Mathematical formula evaluated inside <code>CalculateEmployeeProductivityService</code></div>

<div class="card">
    <div class="card-title">Productivity Score Components & Weightage</div>
    <table>
        <thead>
            <tr>
                <th style="width:25%;">Metric Pillar</th>
                <th style="width:15%;">Max Weight</th>
                <th style="width:30%;">Formula / Calculation</th>
                <th style="width:30%;">Impact on Overall Performance</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Task Completion Rate</strong></td>
                <td><strong>40 Points</strong></td>
                <td><code>(Completed Tasks / Total Assigned) &times; 40</code></td>
                <td>Rewards timely finishing of all assigned work</td>
            </tr>
            <tr>
                <td><strong>Daily Report On-Time Rate</strong></td>
                <td><strong>30 Points</strong></td>
                <td><code>(On-Time Reports / Working Days) &times; 30</code></td>
                <td>Enforces strict 6:00 PM daily check-in discipline</td>
            </tr>
            <tr>
                <td><strong>Quality & Turnaround Index</strong></td>
                <td><strong>20 Points</strong></td>
                <td><code>20 - (Overdue Tasks &times; 5)</code> (Min 0)</td>
                <td>Severe deduction for tasks passing deadline date</td>
            </tr>
            <tr>
                <td><strong>Revision Penalty Index</strong></td>
                <td><strong>10 Points</strong></td>
                <td><code>10 - (Avg Revisions per Task &times; 3)</code> (Min 0)</td>
                <td>Rewards "First-Time Right" high quality work</td>
            </tr>
            <tr style="background:#f1f5f9; font-weight:bold;">
                <td><strong>TOTAL COMPOSITE SCORE</strong></td>
                <td><strong>100 Points</strong></td>
                <td colspan="2"><code>85–100: Top Performer 🌟 | 70–84: Solid Performer ⚡ | &lt; 70: Needs Improvement ⚠️</code></td>
            </tr>
        </tbody>
    </table>
</div>

<!-- AUDIT LOGGING & COMPLIANCE -->
<div class="card card-dark" style="margin-top:8px;">
    <div class="card-title">8. Enterprise Audit Logging & Immutability Protocols</div>
    <div class="step-text">
        • <strong>Task History & Revisions:</strong> Every revision request logs requester user ID, timestamp, and detailed revision notes in <code>task_revisions</code>.<br>
        • <strong>Dual Approval Traceability:</strong> Level-1 Team Lead review and Level-2 Department Head sign-off permanently record user IDs and exact timestamps.<br>
        • <strong>Atomic Sequence Integrity:</strong> Task codes (<code>TSK-YYYY-XXXXX</code>) are allocated inside atomic database locks to prevent sequence collisions.<br>
        • <strong>Tenant Isolation:</strong> All queries, relationships, and policies strictly isolate data by active <code>company_id</code>.
    </div>
</div>

<div class="footer">YM Construction ERP • Department Workflow Standard Operating Procedure (SOP) • Page 4 of 4 • Generated on August 11, 2026</div>

</body>
</html>
HTML;
    }
}
