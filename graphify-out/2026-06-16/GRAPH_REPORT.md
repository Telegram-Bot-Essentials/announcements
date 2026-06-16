# Graph Report - .  (2026-06-16)

## Corpus Check
- Corpus is ~3,904 words - fits in a single context window. You may not need a graph.

## Summary
- 183 nodes · 290 edges · 20 communities (19 shown, 1 thin omitted)
- Extraction: 93% EXTRACTED · 7% INFERRED · 0% AMBIGUOUS · INFERRED: 20 edges (avg confidence: 0.84)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- [[_COMMUNITY_Composer Package Configuration|Composer Package Configuration]]
- [[_COMMUNITY_Telegram Admin UI and Features|Telegram Admin UI and Features]]
- [[_COMMUNITY_Announcements Models and Factories|Announcements Models and Factories]]
- [[_COMMUNITY_Service Provider, Jobs, and Translations|Service Provider, Jobs, and Translations]]
- [[_COMMUNITY_Database Seeders and User Factory|Database Seeders and User Factory]]
- [[_COMMUNITY_Announcement Factory Configuration|Announcement Factory Configuration]]
- [[_COMMUNITY_Announcement Target Factory States|Announcement Target Factory States]]
- [[_COMMUNITY_Workbench Testbench Setup|Workbench Testbench Setup]]
- [[_COMMUNITY_Telegram Admin Answer Handler|Telegram Admin Answer Handler]]
- [[_COMMUNITY_Telegram Admin Reply Key|Telegram Admin Reply Key]]
- [[_COMMUNITY_Workbench Routing and Bootstrap|Workbench Routing and Bootstrap]]

## God Nodes (most connected - your core abstractions)
1. `AnnouncementsQuery` - 22 edges
2. `AnnouncementsFeature` - 22 edges
3. `Announcement` - 18 edges
4. `AnnouncementTarget` - 16 edges
5. `TbeAnnouncementsServiceProvider` - 12 edges
6. `AnnouncementFactory` - 11 edges
7. `AnnouncementTargetFactory` - 11 edges
8. `DeleteAnnouncementJob` - 10 edges
9. `SendAnnouncementJob` - 10 edges
10. `Announcement` - 10 edges

## Surprising Connections (you probably didn't know these)
- `Canvas Config` --references--> `User`  [EXTRACTED]
  canvas.yaml → workbench/app/Models/User.php
- `WorkbenchServiceProvider` --references--> `Canvas Config`  [INFERRED]
  workbench/app/Providers/WorkbenchServiceProvider.php → canvas.yaml
- `Testbench Config` --references--> `DatabaseSeeder`  [EXTRACTED]
  testbench.yaml → workbench/database/seeders/DatabaseSeeder.php
- `Create Announcements Table Migration` --rationale_for--> `Announcement`  [INFERRED]
  database/migrations/2026_06_11_090358_create_announcements_table.php → src/Models/Announcement.php
- `Create Announcement Targets Table Migration` --rationale_for--> `AnnouncementTarget`  [INFERRED]
  database/migrations/2026_06_12_161419_create_announcement_targets_table.php → src/Models/AnnouncementTarget.php

## Import Cycles
- None detected.

## Hyperedges (group relationships)
- **Announcement Delivery Lifecycle** — models_announcement_announcement, models_announcementtarget_announcementtarget, jobs_sendannouncementjob_sendannouncementjob, jobs_deleteannouncementjob_deleteannouncementjob [INFERRED 0.85]
- **Announcement Testing and Seeding Setup** — seeders_announcementseeder_announcementseeder, factories_announcementfactory_announcementfactory, factories_announcementtargetfactory_announcementtargetfactory [INFERRED 0.85]
- **Announcement Localization files** — en_announcements_translations, fa_announcements_translations, src_tbeannouncementsserviceprovider_tbeannouncementsserviceprovider [INFERRED 0.85]
- **Admin Announcements Interaction Flow** — admin_announcementskey_announcementskey, admin_announcementsfeature_announcementsfeature, admin_announcementsquery_announcementsquery, admin_announcementsanswer_announcementsanswer [INFERRED 0.95]
- **Workbench Testing Environment** — providers_workbenchserviceprovider_workbenchserviceprovider, seeders_databaseseeder_databaseseeder, factories_userfactory_userfactory, models_user_user, testbench_config, canvas_config [INFERRED 0.85]

## Communities (20 total, 1 thin omitted)

### Community 0 - "Composer Package Configuration"
Cohesion: 0.06
Nodes (32): authors, autoload, classmap, autoload-dev, psr-4, files, psr-4, description (+24 more)

### Community 1 - "Telegram Admin UI and Features"
Cohesion: 0.15
Nodes (8): AnnouncementsFeature, AnnouncementsQuery, CallbackQuery, Announcement, AnnouncementTarget, Announcement, AnnouncementTarget, TelegramResponse

### Community 2 - "Announcements Models and Factories"
Cohesion: 0.13
Nodes (14): AnnouncementFactory, AnnouncementTargetFactory, Authenticatable, BelongsToTenant, HasFactory, HasMany, Message, Model (+6 more)

### Community 3 - "Service Provider, Jobs, and Translations"
Cohesion: 0.15
Nodes (11): Canvas Working Path Bootstrapper, Composer Package Configuration, English Announcement Translations, Persian Announcement Translations, DeleteAnnouncementJob, SendAnnouncementJob, Create Announcements Table Migration, Create Announcement Targets Table Migration (+3 more)

### Community 4 - "Database Seeders and User Factory"
Cohesion: 0.19
Nodes (9): Bot, BotUser, UserFactory, Factory, Seeder, AnnouncementSeeder, DatabaseSeeder, WithoutModelEvents (+1 more)

### Community 5 - "Announcement Factory Configuration"
Cohesion: 0.29
Nodes (4): Bot, BotUser, static, AnnouncementFactory

### Community 6 - "Announcement Target Factory States"
Cohesion: 0.31
Nodes (3): BotUser, static, AnnouncementTargetFactory

### Community 7 - "Workbench Testbench Setup"
Cohesion: 0.32
Nodes (4): Canvas Config, WorkbenchServiceProvider, ServiceProvider, Testbench Config

### Community 8 - "Telegram Admin Answer Handler"
Cohesion: 0.48
Nodes (3): AnnouncementsAnswer, Announcement, StateAnswer

### Community 10 - "Workbench Routing and Bootstrap"
Cohesion: 0.67
Nodes (3): Workbench Bootstrap, Console Routes, Web Routes

## Knowledge Gaps
- **26 isolated node(s):** `name`, `description`, `type`, `php`, `laravel/framework` (+21 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **1 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Announcement` connect `Announcements Models and Factories` to `Telegram Admin UI and Features`, `Service Provider, Jobs, and Translations`, `Database Seeders and User Factory`, `Announcement Factory Configuration`, `Telegram Admin Answer Handler`?**
  _High betweenness centrality (0.182) - this node is a cross-community bridge._
- **Why does `AnnouncementTarget` connect `Announcements Models and Factories` to `Telegram Admin UI and Features`, `Service Provider, Jobs, and Translations`, `Database Seeders and User Factory`, `Announcement Target Factory States`?**
  _High betweenness centrality (0.147) - this node is a cross-community bridge._
- **Why does `AnnouncementsFeature` connect `Telegram Admin UI and Features` to `Telegram Admin Answer Handler`, `Telegram Admin Reply Key`, `Announcements Models and Factories`?**
  _High betweenness centrality (0.140) - this node is a cross-community bridge._
- **Are the 11 inferred relationships involving `AnnouncementsFeature` (e.g. with `.change()` and `.createAnnouncement()`) actually correct?**
  _`AnnouncementsFeature` has 11 INFERRED edges - model-reasoned connections that need verification._
- **What connects `name`, `description`, `type` to the rest of the system?**
  _26 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Composer Package Configuration` be split into smaller, more focused modules?**
  _Cohesion score 0.06060606060606061 - nodes in this community are weakly interconnected._
- **Should `Telegram Admin UI and Features` be split into smaller, more focused modules?**
  _Cohesion score 0.1455026455026455 - nodes in this community are weakly interconnected._