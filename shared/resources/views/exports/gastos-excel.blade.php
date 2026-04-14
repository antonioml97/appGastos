@php echo '<?xml version="1.0" encoding="UTF-8"?>'; @endphp
@php echo '<?mso-application progid="Excel.Sheet"?>'; @endphp
<Workbook
    xmlns="urn:schemas-microsoft-com:office:spreadsheet"
    xmlns:o="urn:schemas-microsoft-com:office:office"
    xmlns:x="urn:schemas-microsoft-com:office:excel"
    xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
>
    <Styles>
        <Style ss:ID="Title">
            <Font ss:Bold="1" ss:Size="16"/>
        </Style>
        <Style ss:ID="Subtitle">
            <Font ss:Bold="1" ss:Color="#1F2937"/>
            <Interior ss:Color="#E2E8F0" ss:Pattern="Solid"/>
        </Style>
        <Style ss:ID="Header">
            <Font ss:Bold="1"/>
            <Interior ss:Color="#DCE6F1" ss:Pattern="Solid"/>
        </Style>
        <Style ss:ID="Amount">
            <NumberFormat ss:Format="Standard"/>
        </Style>
        <Style ss:ID="Muted">
            <Font ss:Color="#475569"/>
        </Style>
    </Styles>
    <Worksheet ss:Name="Resumen">
        <Table>
            <Column ss:Width="180"/>
            <Column ss:Width="310"/>
            <Column ss:Width="90"/>
            <Row>
                <Cell ss:StyleID="Title"><Data ss:Type="String">AppGastos - Copia de seguridad</Data></Cell>
            </Row>
            <Row>
                <Cell ss:StyleID="Muted"><Data ss:Type="String">Archivo preparado para revisar en Excel y volver a importarlo en AppGastos.</Data></Cell>
            </Row>
            <Row>
                <Cell ss:StyleID="Muted"><Data ss:Type="String">Fecha de exportacion</Data></Cell>
                <Cell><Data ss:Type="String">{{ now()->format('d/m/Y H:i') }}</Data></Cell>
            </Row>
            <Row></Row>
            <Row>
                <Cell ss:StyleID="Subtitle"><Data ss:Type="String">Hoja</Data></Cell>
                <Cell ss:StyleID="Subtitle"><Data ss:Type="String">Contenido</Data></Cell>
                <Cell ss:StyleID="Subtitle"><Data ss:Type="String">Registros</Data></Cell>
            </Row>
            <Row>
                <Cell><Data ss:Type="String">Gastos</Data></Cell>
                <Cell><Data ss:Type="String">Movimientos de gasto ordenados por fecha.</Data></Cell>
                <Cell ss:StyleID="Amount"><Data ss:Type="Number">{{ count($rows) }}</Data></Cell>
            </Row>
            <Row>
                <Cell><Data ss:Type="String">Ingresos</Data></Cell>
                <Cell><Data ss:Type="String">Movimientos de ingreso ordenados por fecha.</Data></Cell>
                <Cell ss:StyleID="Amount"><Data ss:Type="Number">{{ count($incomeRows) }}</Data></Cell>
            </Row>
            <Row>
                <Cell><Data ss:Type="String">Movimientos fijos</Data></Cell>
                <Cell><Data ss:Type="String">Gastos e ingresos recurrentes con su dia y estado.</Data></Cell>
                <Cell ss:StyleID="Amount"><Data ss:Type="Number">{{ count($fixedEntryRows) }}</Data></Cell>
            </Row>
            <Row>
                <Cell><Data ss:Type="String">Categorias</Data></Cell>
                <Cell><Data ss:Type="String">Listado de categorias disponibles y uso acumulado.</Data></Cell>
                <Cell ss:StyleID="Amount"><Data ss:Type="Number">{{ count($categoryRows) }}</Data></Cell>
            </Row>
            <Row>
                <Cell><Data ss:Type="String">Cuentas</Data></Cell>
                <Cell><Data ss:Type="String">Saldos y configuracion de las cuentas normales y de ahorro.</Data></Cell>
                <Cell ss:StyleID="Amount"><Data ss:Type="Number">{{ count($accountRows) }}</Data></Cell>
            </Row>
        </Table>
    </Worksheet>
    <Worksheet ss:Name="Gastos">
        <Table>
            <Column ss:Width="85"/>
            <Column ss:Width="220"/>
            <Column ss:Width="160"/>
            <Column ss:Width="90"/>
            <Column ss:Width="260"/>
            <Row>
                <Cell ss:StyleID="Header"><Data ss:Type="String">Fecha</Data></Cell>
                <Cell ss:StyleID="Header"><Data ss:Type="String">Titulo</Data></Cell>
                <Cell ss:StyleID="Header"><Data ss:Type="String">Categoria</Data></Cell>
                <Cell ss:StyleID="Header"><Data ss:Type="String">Importe</Data></Cell>
                <Cell ss:StyleID="Header"><Data ss:Type="String">Observaciones</Data></Cell>
            </Row>
            @forelse ($rows as $row)
                <Row>
                    <Cell><Data ss:Type="String">{{ $row['fecha'] }}</Data></Cell>
                    <Cell><Data ss:Type="String">{{ $row['titulo'] }}</Data></Cell>
                    <Cell><Data ss:Type="String">{{ $row['categoria'] }}</Data></Cell>
                    <Cell ss:StyleID="Amount"><Data ss:Type="Number">{{ number_format((float) $row['importe'], 2, '.', '') }}</Data></Cell>
                    <Cell><Data ss:Type="String">{{ $row['observaciones'] }}</Data></Cell>
                </Row>
            @empty
                <Row>
                    <Cell><Data ss:Type="String">No hay gastos para exportar.</Data></Cell>
                </Row>
            @endforelse
        </Table>
    </Worksheet>
    <Worksheet ss:Name="Ingresos">
        <Table>
            <Column ss:Width="85"/>
            <Column ss:Width="240"/>
            <Column ss:Width="90"/>
            <Column ss:Width="280"/>
            <Row>
                <Cell ss:StyleID="Header"><Data ss:Type="String">Fecha</Data></Cell>
                <Cell ss:StyleID="Header"><Data ss:Type="String">Titulo</Data></Cell>
                <Cell ss:StyleID="Header"><Data ss:Type="String">Importe</Data></Cell>
                <Cell ss:StyleID="Header"><Data ss:Type="String">Observaciones</Data></Cell>
            </Row>
            @forelse ($incomeRows as $row)
                <Row>
                    <Cell><Data ss:Type="String">{{ $row['fecha'] }}</Data></Cell>
                    <Cell><Data ss:Type="String">{{ $row['titulo'] }}</Data></Cell>
                    <Cell ss:StyleID="Amount"><Data ss:Type="Number">{{ number_format((float) $row['importe'], 2, '.', '') }}</Data></Cell>
                    <Cell><Data ss:Type="String">{{ $row['observaciones'] }}</Data></Cell>
                </Row>
            @empty
                <Row>
                    <Cell><Data ss:Type="String">No hay ingresos para exportar.</Data></Cell>
                </Row>
            @endforelse
        </Table>
    </Worksheet>
    <Worksheet ss:Name="Movimientos fijos">
        <Table>
            <Column ss:Width="90"/>
            <Column ss:Width="220"/>
            <Column ss:Width="150"/>
            <Column ss:Width="90"/>
            <Column ss:Width="55"/>
            <Column ss:Width="65"/>
            <Column ss:Width="250"/>
            <Row>
                <Cell ss:StyleID="Header"><Data ss:Type="String">Tipo</Data></Cell>
                <Cell ss:StyleID="Header"><Data ss:Type="String">Titulo</Data></Cell>
                <Cell ss:StyleID="Header"><Data ss:Type="String">Categoria</Data></Cell>
                <Cell ss:StyleID="Header"><Data ss:Type="String">Importe</Data></Cell>
                <Cell ss:StyleID="Header"><Data ss:Type="String">Dia</Data></Cell>
                <Cell ss:StyleID="Header"><Data ss:Type="String">Activo</Data></Cell>
                <Cell ss:StyleID="Header"><Data ss:Type="String">Observaciones</Data></Cell>
            </Row>
            @forelse ($fixedEntryRows as $row)
                <Row>
                    <Cell><Data ss:Type="String">{{ ucfirst($row['tipo']) }}</Data></Cell>
                    <Cell><Data ss:Type="String">{{ $row['titulo'] }}</Data></Cell>
                    <Cell><Data ss:Type="String">{{ $row['categoria'] }}</Data></Cell>
                    <Cell ss:StyleID="Amount"><Data ss:Type="Number">{{ number_format((float) $row['importe'], 2, '.', '') }}</Data></Cell>
                    <Cell ss:StyleID="Amount"><Data ss:Type="Number">{{ (int) $row['dia'] }}</Data></Cell>
                    <Cell><Data ss:Type="String">{{ $row['activo'] }}</Data></Cell>
                    <Cell><Data ss:Type="String">{{ $row['observaciones'] }}</Data></Cell>
                </Row>
            @empty
                <Row>
                    <Cell><Data ss:Type="String">No hay movimientos fijos para exportar.</Data></Cell>
                </Row>
            @endforelse
        </Table>
    </Worksheet>
    <Worksheet ss:Name="Categorias">
        <Table>
            <Column ss:Width="180"/>
            <Column ss:Width="90"/>
            <Column ss:Width="140"/>
            <Column ss:Width="90"/>
            <Row>
                <Cell ss:StyleID="Header"><Data ss:Type="String">Nombre</Data></Cell>
                <Cell ss:StyleID="Header"><Data ss:Type="String">Color</Data></Cell>
                <Cell ss:StyleID="Header"><Data ss:Type="String">Icono</Data></Cell>
                <Cell ss:StyleID="Header"><Data ss:Type="String">Movimientos</Data></Cell>
            </Row>
            @forelse ($categoryRows as $row)
                <Row>
                    <Cell><Data ss:Type="String">{{ $row['nombre'] }}</Data></Cell>
                    <Cell><Data ss:Type="String">{{ $row['color'] }}</Data></Cell>
                    <Cell><Data ss:Type="String">{{ $row['icono'] }}</Data></Cell>
                    <Cell ss:StyleID="Amount"><Data ss:Type="Number">{{ $row['movimientos'] }}</Data></Cell>
                </Row>
            @empty
                <Row>
                    <Cell><Data ss:Type="String">No hay categorias para exportar.</Data></Cell>
                </Row>
            @endforelse
        </Table>
    </Worksheet>
    <Worksheet ss:Name="Cuentas">
        <Table>
            <Column ss:Width="180"/>
            <Column ss:Width="90"/>
            <Column ss:Width="95"/>
            <Column ss:Width="95"/>
            <Column ss:Width="95"/>
            <Column ss:Width="120"/>
            <Row>
                <Cell ss:StyleID="Header"><Data ss:Type="String">Nombre</Data></Cell>
                <Cell ss:StyleID="Header"><Data ss:Type="String">Tipo</Data></Cell>
                <Cell ss:StyleID="Header"><Data ss:Type="String">Saldo inicial</Data></Cell>
                <Cell ss:StyleID="Header"><Data ss:Type="String">Saldo actual</Data></Cell>
                <Cell ss:StyleID="Header"><Data ss:Type="String">Ahorro mensual</Data></Cell>
                <Cell ss:StyleID="Header"><Data ss:Type="String">Ultimo mes ahorro</Data></Cell>
            </Row>
            @forelse ($accountRows as $row)
                <Row>
                    <Cell><Data ss:Type="String">{{ $row['nombre'] }}</Data></Cell>
                    <Cell><Data ss:Type="String">{{ ucfirst($row['tipo']) }}</Data></Cell>
                    <Cell ss:StyleID="Amount"><Data ss:Type="Number">{{ number_format((float) $row['saldo_inicial'], 2, '.', '') }}</Data></Cell>
                    <Cell ss:StyleID="Amount"><Data ss:Type="Number">{{ number_format((float) $row['saldo_actual'], 2, '.', '') }}</Data></Cell>
                    <Cell ss:StyleID="Amount"><Data ss:Type="Number">{{ number_format((float) ($row['ahorro_mensual'] ?? 0), 2, '.', '') }}</Data></Cell>
                    <Cell><Data ss:Type="String">{{ $row['ultimo_mes_ahorro_aplicado'] }}</Data></Cell>
                </Row>
            @empty
                <Row>
                    <Cell><Data ss:Type="String">No hay cuentas para exportar.</Data></Cell>
                </Row>
            @endforelse
        </Table>
    </Worksheet>
</Workbook>
