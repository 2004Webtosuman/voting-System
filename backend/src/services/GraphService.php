<?php
require_once __DIR__ . '/../repositories/Edges.php';

class GraphService {
    private EdgesRepository $edgesRepository;

    public function __construct() {
        $this->edgesRepository = new EdgesRepository();
    }

    public function dijkstra(int $startSlotId, callable $isTargetCallable): ?array {
        $dist = [];
        $prev = [];
        $visited = [];
        $queue = new SplPriorityQueue();
        $queue->setExtractFlags(SplPriorityQueue::EXTR_BOTH);

        $dist[$startSlotId] = 0.0;
        $queue->insert($startSlotId, 0.0);

        while (!$queue->isEmpty()) {
            $node = $queue->extract();
            $u = $node['data'];
            if (isset($visited[$u])) continue;
            $visited[$u] = true;

            if ($isTargetCallable($u)) {
                $path = [];
                $cur = $u;
                while (isset($prev[$cur])) {
                    array_unshift($path, $cur);
                    $cur = $prev[$cur];
                }
                array_unshift($path, $startSlotId);
                return ['target' => $u, 'distance' => $dist[$u], 'path' => $path];
            }

            foreach ($this->edgesRepository->neighbors($u) as $edge) {
                $v = intval($edge['toSlotId']);
                $weight = floatval($edge['weight']);
                $alt = ($dist[$u] ?? INF) + $weight;
                if ($alt < ($dist[$v] ?? INF)) {
                    $dist[$v] = $alt;
                    $prev[$v] = $u;
                    $queue->insert($v, -$alt);
                }
            }
        }
        return null;
    }
}