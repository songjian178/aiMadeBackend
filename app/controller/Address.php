<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use think\facade\Db;

class Address extends BaseController
{
    /**
     * 新增地址
     * @return \think\Response
     */
    public function create()
    {
        $userId = $this->getCurrentUserId();

        $addressCount = Db::name('user_address')
            ->where('user_id', $userId)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->count();
        if ($addressCount >= 5) {
            return $this->error('最多只能添加5个地址');
        }

        $data = $this->request->post();
        $requiredFields = ['recipient', 'phone', 'province', 'city', 'district', 'address'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return $this->error('参数不完整');
            }
        }

        $isDefault = (int)($data['is_default'] ?? 0) === 1 ? 1 : 0;
        Db::startTrans();
        try {
            if ($isDefault === 1) {
                Db::name('user_address')
                    ->where('user_id', $userId)
                    ->where('status', 1)
                    ->update(['is_default' => 0]);
            }

            $addressId = Db::name('user_address')->insertGetId([
                'user_id' => $userId,
                'recipient' => $data['recipient'],
                'phone' => $data['phone'],
                'province' => $data['province'],
                'city' => $data['city'],
                'district' => $data['district'],
                'address' => $data['address'],
                'zip_code' => $data['zip_code'] ?? null,
                'is_default' => $isDefault,
                'status' => 1
            ]);

            Db::commit();
            $this->writeLog('address_create', '用户新增地址', (int)$userId);
            return $this->success(['id' => $addressId], '地址新增成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('地址新增失败，请稍后重试');
        }
    }

    /**
     * 删除地址（软删除）
     * @return \think\Response
     */
    public function delete()
    {
        $userId = $this->getCurrentUserId();

        $addressId = (int)$this->request->post('id');
        if ($addressId <= 0) {
            return $this->error('参数不完整');
        }

        $address = Db::name('user_address')
            ->where('id', $addressId)
            ->where('user_id', $userId)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->find();

        if (!$address) {
            return $this->error('地址不存在');
        }

        Db::startTrans();
        try {
            Db::name('user_address')
                ->where('id', $addressId)
                ->update([
                    'status' => 0,
                    'deleted_at' => date('Y-m-d H:i:s')
                ]);

            // 删除默认地址后，自动设置最新地址为默认地址
            if ((int)$address['is_default'] === 1) {
                $latestAddress = Db::name('user_address')
                    ->where('user_id', $userId)
                    ->where('status', 1)
                    ->whereNull('deleted_at')
                    ->order('id', 'desc')
                    ->find();

                if ($latestAddress) {
                    Db::name('user_address')
                        ->where('id', $latestAddress['id'])
                        ->update(['is_default' => 1]);
                }
            }

            Db::commit();
            $this->writeLog('address_delete', '用户删除地址', (int)$userId);
            return $this->success(null, '地址删除成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('地址删除失败，请稍后重试');
        }
    }

    /**
     * 修改地址
     * @return \think\Response
     */
    public function update()
    {
        $userId = $this->getCurrentUserId();

        $data = $this->request->post();
        $addressId = (int)($data['id'] ?? 0);
        if ($addressId <= 0) {
            return $this->error('参数不完整');
        }

        $address = Db::name('user_address')
            ->where('id', $addressId)
            ->where('user_id', $userId)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->find();

        if (!$address) {
            return $this->error('地址不存在');
        }

        $updateData = [];
        $allowedFields = ['recipient', 'phone', 'province', 'city', 'district', 'address', 'zip_code'];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if (isset($data['is_default'])) {
            $updateData['is_default'] = (int)$data['is_default'] === 1 ? 1 : 0;
        }

        if (empty($updateData)) {
            return $this->error('没有可更新的内容');
        }

        Db::startTrans();
        try {
            if (($updateData['is_default'] ?? 0) === 1) {
                Db::name('user_address')
                    ->where('user_id', $userId)
                    ->where('status', 1)
                    ->update(['is_default' => 0]);
            }

            Db::name('user_address')->where('id', $addressId)->update($updateData);

            Db::commit();
            $this->writeLog('address_update', '用户修改地址', (int)$userId);
            return $this->success(null, '地址修改成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('地址修改失败，请稍后重试');
        }
    }

    /**
     * 查询当前用户所有地址
     * @return \think\Response
     */
    public function list()
    {
        $userId = $this->getCurrentUserId();

        $list = Db::name('user_address')
            ->field('id,recipient,phone,province,city,district,address,zip_code,is_default,created_at,updated_at')
            ->where('user_id', $userId)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->order('is_default', 'desc')
            ->order('id', 'desc')
            ->select()
            ->toArray();

        return $this->success($list, '获取地址列表成功');
    }

}
