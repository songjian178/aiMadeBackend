<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use think\facade\Db;

class Entity extends BaseController
{
    /**
     * 获取实体分类列表（仅返回在线可用）
     * @return \think\Response
     */
    public function categoryList()
    {
        $list = Db::name('entity_category')
            ->field('id,name,price,validity_period,render_count,description,image_url,sort_order')
            ->where('status', 1)
            ->where('is_display', 1)
            ->whereNull('deleted_at')
            ->order('sort_order', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        $categoryIds = array_column($list, 'id');
        $bannerMap = [];
        $attrMap = [];
        if (!empty($categoryIds)) {
            $banners = Db::name('entity_category_banner')
                ->field('category_id,image_url,sort')
                ->whereIn('category_id', $categoryIds)
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->order('sort', 'asc')
                ->order('id', 'asc')
                ->select()
                ->toArray();

            foreach ($banners as $banner) {
                $cid = (int)$banner['category_id'];
                if (!isset($bannerMap[$cid])) {
                    $bannerMap[$cid] = [];
                }
                $url = (string)($banner['image_url'] ?? '');
                if ($url !== '') {
                    $bannerMap[$cid][] = $url;
                }
            }

            $attrs = Db::name('entity_attribute')
                ->field('id,category_id,attr_name,sort')
                ->whereIn('category_id', $categoryIds)
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->order('sort', 'asc')
                ->order('id', 'asc')
                ->select()
                ->toArray();

            $attrIds = [];
            foreach ($attrs as $attr) {
                $attrId = (int)$attr['id'];
                $attrIds[] = $attrId;
            }

            $valueMap = [];
            if (!empty($attrIds)) {
                $values = Db::name('entity_attribute_value')
                    ->field('id,attribute_id,attr_value,is_default,remark,sort')
                    ->whereIn('attribute_id', $attrIds)
                    ->where('status', 1)
                    ->whereNull('deleted_at')
                    ->order('sort', 'asc')
                    ->order('id', 'asc')
                    ->select()
                    ->toArray();

                foreach ($values as $valueRow) {
                    $aid = (int)$valueRow['attribute_id'];
                    if (!isset($valueMap[$aid])) {
                        $valueMap[$aid] = [];
                    }
                    $valueMap[$aid][] = [
                        'id' => (int)$valueRow['id'],
                        'value' => (string)($valueRow['attr_value'] ?? ''),
                        'is_default' => (int)($valueRow['is_default'] ?? 0),
                        'remark' => (string)($valueRow['remark'] ?? ''),
                    ];
                }
            }

            foreach ($attrs as $attr) {
                $cid = (int)$attr['category_id'];
                $aid = (int)$attr['id'];
                if (!isset($attrMap[$cid])) {
                    $attrMap[$cid] = [];
                }
                $attrMap[$cid][] = [
                    'attribute_id' => $aid,
                    'attribute_name' => (string)($attr['attr_name'] ?? ''),
                    'values' => $valueMap[$aid] ?? [],
                ];
            }
        }

        foreach ($list as &$row) {
            $cid = (int)$row['id'];
            $row['urls'] = $bannerMap[$cid] ?? [];
            $row['attributes'] = $attrMap[$cid] ?? [];
        }
        unset($row);

        return $this->success($list, '获取实体分类成功');
    }

    /**
     * 获取实体详情
     * @return \think\Response
     */
    public function categoryDetail()
    {
        $categoryId = (int)$this->request->get('category_id', 0);
        if ($categoryId <= 0) {
            return $this->error('参数不完整');
        }

        $detail = Db::name('entity_category')
            ->field('id,name,price,validity_period,render_count,description,image_url,sort_order,placeholder')
            ->where('id', $categoryId)
            ->where('status', 1)
            ->where('is_display', 1)
            ->whereNull('deleted_at')
            ->find();

        if (!$detail) {
            return $this->error('实体分类不存在或不可用');
        }

        $banners = Db::name('entity_category_banner')
            ->field('image_url')
            ->where('category_id', $categoryId)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        $urls = [];
        foreach ($banners as $banner) {
            $url = (string)($banner['image_url'] ?? '');
            if ($url !== '') {
                $urls[] = $url;
            }
        }

        $detail['urls'] = $urls;
        $attributes = Db::name('entity_attribute')
            ->field('id,attr_name')
            ->where('category_id', $categoryId)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        $attrIds = array_map(static fn($row) => (int)$row['id'], $attributes);
        $valueMap = [];
        if (!empty($attrIds)) {
            $values = Db::name('entity_attribute_value')
                ->field('id,attribute_id,attr_value,is_default,remark')
                ->whereIn('attribute_id', $attrIds)
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->order('sort', 'asc')
                ->order('id', 'asc')
                ->select()
                ->toArray();

            foreach ($values as $valueRow) {
                $aid = (int)$valueRow['attribute_id'];
                if (!isset($valueMap[$aid])) {
                    $valueMap[$aid] = [];
                }
                $valueMap[$aid][] = [
                    'id' => (int)$valueRow['id'],
                    'value' => (string)($valueRow['attr_value'] ?? ''),
                    'is_default' => (int)($valueRow['is_default'] ?? 0),
                    'remark' => (string)($valueRow['remark'] ?? ''),
                ];
            }
        }

        $detail['attributes'] = [];
        foreach ($attributes as $attrRow) {
            $aid = (int)$attrRow['id'];
            $detail['attributes'][] = [
                'attribute_id' => $aid,
                'attribute_name' => (string)($attrRow['attr_name'] ?? ''),
                'values' => $valueMap[$aid] ?? [],
            ];
        }

        return $this->success($detail, '获取实体详情成功');
    }

    /**
     * 获取实体分类列表（含当前用户可使用次数）
     * @return \think\Response
     */
    public function categoryListWithUsage()
    {
        $userId = $this->getCurrentUserId();

        $list = Db::name('entity_category')
            ->alias('ec')
            ->leftJoin('user_purchased_entity upe', "upe.category_id = ec.id AND upe.user_id = {$userId} AND upe.status = 1 AND upe.deleted_at IS NULL AND upe.expire_time > '" . date('Y-m-d H:i:s') . "'")
            ->field('ec.id,ec.name,ec.price,ec.validity_period,ec.render_count,ec.description,ec.image_url,ec.sort_order,IFNULL(SUM(upe.remaining_renders),0) as user_available_count')
            ->where('ec.status', 1)
            ->where('ec.is_display', 1)
            ->whereNull('ec.deleted_at')
            ->group('ec.id,ec.name,ec.price,ec.validity_period,ec.render_count,ec.description,ec.image_url,ec.sort_order')
            ->order('ec.sort_order', 'asc')
            ->order('ec.id', 'asc')
            ->select()
            ->toArray();

        return $this->success($list, '获取实体分类成功');
    }

    /**
     * 用户制作历史
     * 外层：用户当前购买的实体权益（未过期）
     * 内层：该权益下已生成的图片
     * @return \think\Response
     */
    public function makeHistory()
    {
        $userId = $this->getCurrentUserId();
        $now = date('Y-m-d H:i:s');

        $rows = Db::name('user_purchased_entity')
            ->alias('upe')
            ->join('entity_category ec', 'ec.id = upe.category_id', 'inner')
            ->leftJoin('entity_order o', 'o.id = upe.order_id AND o.user_id = upe.user_id AND o.status = 1 AND o.deleted_at IS NULL AND o.payment_status = 1')
            ->leftJoin('order_corpus oc', 'oc.order_id = o.id AND oc.status = 1 AND oc.deleted_at IS NULL')
            ->leftJoin('generated_image gi', 'gi.corpus_id = oc.id AND gi.status = 1 AND gi.deleted_at IS NULL')
            ->field('upe.id as purchased_entity_id,upe.category_id,upe.order_id,upe.expire_time,upe.remaining_renders,ec.name as category_name,' .
                'gi.image_url,gi.render_url,gi.corpus_id,oc.prompt')
            ->where('upe.user_id', $userId)
            ->where('upe.status', 1)
            ->whereNull('upe.deleted_at')
            ->where('upe.expire_time', '>', $now)
            ->where('ec.status', 1)
            ->whereNull('ec.deleted_at')
            ->order('upe.id', 'desc')
            ->select()
            ->toArray();

        // 以 purchased_entity_id 聚合二维结构
        $map = [];
        foreach ($rows as $row) {
            $key = (int)$row['purchased_entity_id'];
            if (!isset($map[$key])) {
                $map[$key] = [
                    'purchased_entity_id' => (int)$row['purchased_entity_id'],
                    'category_id' => (int)$row['category_id'],
                    'category_name' => (string)$row['category_name'],
                    'order_id' => (int)$row['order_id'],
                    'remaining_renders' => (int)($row['remaining_renders'] ?? 0),
                    'expire_time' => $row['expire_time'],
                    'images' => []
                ];
            }

            // 左连接时无图片：gi.corpus_id 可能为空
            $corpusId = $row['corpus_id'] ?? null;
            if ($corpusId !== null && $corpusId !== '') {
                $map[$key]['images'][] = [
                    'image_url' => $row['image_url'],
                    'render_url' => $row['render_url'],
                    'corpus_id' => (int)$row['corpus_id'],
                    'prompt' => $row['prompt']
                ];
            }
        }

        return $this->success(array_values($map), '获取用户制作历史成功');
    }
}
