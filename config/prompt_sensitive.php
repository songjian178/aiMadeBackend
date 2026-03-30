<?php
declare(strict_types=1);

/**
 * 图片生成 prompt 敏感词配置（可按业务继续扩充）。
 * 匹配方式为：对输入与词表均做空白折叠后做子串包含判断（不区分英文大小写）。
 */
return [
    'message' => '内容包含违规信息，请修改提示词后重试',
    'keywords' => [
        // 涉黄相关（示例词，可扩充）
        '色情', '淫秽', '裸体', '露骨', '性交', '做爱', '强奸', '幼女', '萝莉控','下体','口含','舔舐','乳交','口交','操逼',
        // 涉赌相关
        '赌博', '赌场', '博彩', '下注', '赌球', '六合彩', '老虎机', '网赌',
        // 涉毒相关
        '毒品', '贩毒', '冰毒', '海洛因', '大麻', '吸毒', '制毒',
        // 政治与违法煽动等（示例，可扩充）
        '颠覆政权', '分裂国家', '邪教组织', '恐怖活动', '制造爆炸','习近平','国旗','国歌','消防车','党员','政治',
        // 英文对应词（与以上中文词对应追加）
        'pornography', 'obscene', 'nude', 'explicit', 'sexual intercourse', 'make love', 'rape', 'underage girl', 'lolicon', 'genitals', 'oral inclusion', 'licking', 'breast sex', 'oral sex', 'fuck pussy',
        'gambling', 'casino', 'betting', 'place bet', 'football betting', 'mark six', 'slot machine', 'online gambling',
        'drugs', 'drug trafficking', 'methamphetamine', 'heroin', 'marijuana', 'drug use', 'drug manufacturing',
        'subvert state power', 'separatism', 'cult organization', 'terrorist activity', 'make explosives', 'xi jinping', 'national flag', 'national anthem', 'fire truck', 'party member', 'politics'
    ],
];
