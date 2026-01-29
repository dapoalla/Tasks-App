<?php
require_once 'db.php';
include 'header.php';
?>

<div class="max-w-6xl mx-auto px-4 py-8">
    
    <!-- Hero Section with Main Link -->
    <div class="bg-gradient-to-r from-blue-900 to-indigo-900 rounded-2xl p-8 mb-12 text-center shadow-xl border border-blue-700/50 relative overflow-hidden group">
        <div class="absolute top-0 left-0 w-full h-full bg-white/5 opacity-0 group-hover:opacity-10 transition-opacity"></div>
        <h1 class="text-3xl md:text-4xl font-black text-white mb-4 tracking-tight">ICT Documentation Wiki</h1>
        <p class="text-blue-200 mb-8 max-w-2xl mx-auto text-lg">Centralized repository for network infrastructure, backup configurations, changelogs, and topology diagrams managed by the ICT department.</p>
        
        <a href="https://afweca.sharepoint.com/sites/AFMICTDocumentationWIKI" target="_blank" 
           class="inline-flex items-center bg-white text-blue-900 hover:bg-blue-50 font-bold py-4 px-8 rounded-xl shadow-lg transition-all transform hover:scale-105 group-hover:shadow-blue-500/20">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 mr-3 text-[#0078d4]">
                <path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z" />
            </svg>
            Access Full Documentation on SharePoint
        </a>
    </div>

    <!-- Documentation Categories Grid -->
    <h2 class="text-xl font-bold text-white mb-6 flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 mr-2 text-accent-500">
          <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
        </svg>
        Included Documentation Topics
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        
        <!-- Tile Template -->
        <?php
        $topics = [
            [
                'title' => 'Onigbongbo WIFI Details',
                'subtitle' => 'DS Side Configuration',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 0 1 7.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 0 1 1.06 0Z" />',
                'color' => 'blue'
            ],
            [
                'title' => 'UNIFI Controller',
                'subtitle' => 'Access Point Management',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12h1.5m12 0h-1.5m-1.5 6.75v1.5m-9-1.5v1.5m6-13.5V3m0 0L8.25 3a3 3 0 1 0 0 6h12a3 3 0 1 0 0-6h-4.5Z" />',
                'color' => 'indigo'
            ],
            [
                'title' => 'Omada Controller',
                'subtitle' => 'Network Management',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />',
                'color' => 'cyan'
            ],
            [
                'title' => 'AFM WECA Podcast',
                'subtitle' => 'Setup & Streaming',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 0 0 6-6v-1.5m-6 7.5a6 6 0 0 1-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 0 1-3-3V4.5a3 3 0 1 1 6 0v8.25a3 3 0 0 1-3 3Z" />',
                'color' => 'rose'
            ],
            [
                'title' => 'HyperV Server',
                'subtitle' => 'Virtualization Host',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6m-16.5-3a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3m-19.5 0a4.5 4.5 0 0 1 .9-2.7L5.737 5.1a3.375 3.375 0 0 1 2.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 0 1 .9 2.7m0 0a3 3 0 0 1-3 3m0 3h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Zm-3 6h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Z" />',
                'color' => 'violet'
            ],
            [
                'title' => 'LAN Documentation',
                'subtitle' => 'Infrastructure Map',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m-9 0V12.628a5.23 5.23 0 0 1 1.908-4.044L8.715 7.84a1.75 1.75 0 0 0 .543-1.29v-1.17a1.75 1.75 0 0 1 1.745-1.75h1.994a1.75 1.75 0 0 1 1.745 1.75v1.17c0 .533.246 1.033.543 1.29l.792.748a5.23 5.23 0 0 1 1.908 4.043V17.25H6Z" />',
                'color' => 'emerald'
            ],
            [
                'title' => 'Sophos Firewall',
                'subtitle' => 'Security Gateway',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />',
                'color' => 'red'
            ],
            [
                'title' => 'QNAP NAS',
                'subtitle' => 'Storage & Backup',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />',
                'color' => 'amber'
            ],
            [
                'title' => 'LAN Streamer',
                'subtitle' => 'Internal Media',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />',
                'color' => 'teal'
            ],
            [
                'title' => 'IP Surveillance',
                'subtitle' => 'CCTV Systems',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />',
                'color' => 'slate'
            ],
            [
                'title' => 'IP Address Mgmt',
                'subtitle' => 'Network Allocation',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />',
                'color' => 'orange'
            ],
             [
                'title' => 'ZKTeco Access Control',
                'subtitle' => 'Security Doors',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />',
                'color' => 'zinc'
            ],
            [
                'title' => 'IP PBX Appliance',
                'subtitle' => 'Telephony System',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M14.25 9.75v-4.5m0 4.5h4.5m-4.5 0 6-6m-3 18c-8.284 0-15-6.716-15-15V4.5A2.25 2.25 0 0 1 4.5 2.25h1.372c.516 0 .966.351 1.091.852l1.106 4.423c.11.44-.054.902-.386 1.179l-1.371 1.148c1.626 3.016 4.275 5.666 7.291 7.291l1.148-1.372c.277-.332.739-.496 1.179-.386l4.423 1.106c.5.125.852.575.852 1.091V19.5a2.25 2.25 0 0 1-2.25 2.25h-2.25Z" />',
                'color' => 'lime'
            ]
        ];

        // Specific color mappings to tailwind classes
        function getColorClasses($color) {
            $map = [
                'blue' => 'bg-blue-500/10 text-blue-500 border-blue-500/20 group-hover:border-blue-500/50',
                'indigo' => 'bg-indigo-500/10 text-indigo-500 border-indigo-500/20 group-hover:border-indigo-500/50',
                'cyan' => 'bg-cyan-500/10 text-cyan-500 border-cyan-500/20 group-hover:border-cyan-500/50',
                'rose' => 'bg-rose-500/10 text-rose-500 border-rose-500/20 group-hover:border-rose-500/50',
                'violet' => 'bg-violet-500/10 text-violet-500 border-violet-500/20 group-hover:border-violet-500/50',
                'emerald' => 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20 group-hover:border-emerald-500/50',
                'red' => 'bg-red-500/10 text-red-500 border-red-500/20 group-hover:border-red-500/50',
                'amber' => 'bg-amber-500/10 text-amber-500 border-amber-500/20 group-hover:border-amber-500/50',
                'teal' => 'bg-teal-500/10 text-teal-500 border-teal-500/20 group-hover:border-teal-500/50',
                'slate' => 'bg-slate-500/10 text-slate-500 border-slate-500/20 group-hover:border-slate-500/50',
                'orange' => 'bg-orange-500/10 text-orange-500 border-orange-500/20 group-hover:border-orange-500/50',
                'zinc' => 'bg-zinc-500/10 text-purple-500 border-zinc-500/20 group-hover:border-purple-500/50',
                'lime' => 'bg-lime-500/10 text-lime-500 border-lime-500/20 group-hover:border-lime-500/50',
            ];
            return $map[$color] ?? $map['blue'];
        }

        foreach ($topics as $topic): 
            $classes = getColorClasses($topic['color']);
        ?>
            <a href="https://afweca.sharepoint.com/sites/AFMICTDocumentationWIKI" target="_blank" class="block group">
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 shadow-lg hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 h-full flex items-start">
                    <div class="p-3 rounded-lg mr-4 shrink-0 <?php echo $classes; ?> border transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                          <?php echo $topic['icon']; ?>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-white font-bold text-lg mb-1 group-hover:text-accent-400 transition-colors"><?php echo htmlspecialchars($topic['title']); ?></h3>
                        <p class="text-gray-400 text-sm"><?php echo htmlspecialchars($topic['subtitle']); ?></p>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
    
    <div class="mt-12 text-center text-gray-500 text-sm">
        All documentation is securely hosted on SharePoint. <br>
        Access requires AFM WECA Microsoft credentials.
    </div>

</div>

<?php include 'footer.php'; ?>
