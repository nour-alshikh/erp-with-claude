<?php

namespace App\Modules\Sales\Repositories;

use App\Base\BaseRepository;
use App\Modules\Sales\Models\Quotation;
use App\Modules\Sales\Repositories\Interfaces\QuotationRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class QuotationRepository extends BaseRepository implements QuotationRepositoryInterface
{
    public function __construct(Quotation $model)
    {
        parent::__construct($model);
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['customer'])->orderByDesc('date')->paginate($perPage);
    }

    public function findOrFail(int $id): Quotation
    {
        return $this->model->with(['customer', 'lines.product'])->findOrFail($id);
    }

    public function create(array $data): Quotation
    {
        return $this->model->create($data);
    }

    public function update(Quotation $quotation, array $data): Quotation
    {
        $quotation->update($data);
        return $quotation->fresh(['customer', 'lines.product']);
    }
}
