# UiTPAS test data (ACC)

Fixtures for the `@external` scenarios in `features/uitpas/`. They live on ACC because UiTPAS only knows events it received from the acceptance environment. A locally created event never reaches it.

| | |
|---|---|
| Organizer | [`825c07af-cb70-4173-871e-dd7bb4755c2e`](https://acc.uitdatabank.be/organizers/825c07af-cb70-4173-871e-dd7bb4755c2e) "UiTPAS organizer for Acceptance Test Entry API [Do not change]" |
| Event | [`5ed91e8d-a3fd-4ab9-9498-f4b0ebdd5ee8`](https://acc.uitdatabank.be/events/5ed91e8d-a3fd-4ab9-9498-f4b0ebdd5ee8) "UiTPAS event for Acceptance Test Entry API [Do not change]" |
| Card systems | 5 UiTPAS Regio Gent, 3 Paspartoe |

Neither card system has a manual distribution key, so the distribution key scenario cannot run yet.

## How it was created

1. Organizer created on ACC through UDB3 frontend.
2. Erwin Morrhey created a UiTPAS balie and linked it to that organizer, in Gent and Paspartoe.
3. The `UiTPAS Gent` and `Paspartoe` labels were added manually as hidden labels on the organizer. Linking the balie did not produce an `OrganizerCardSystemsUpdated` over AMQP.
4. Event created on ACC with that organizer, with a base tariff of 15 EUR.

## Gotchas

- The event needs `priceInfo` before UiTPAS registers it. Without a tariff `GET api-acc.uitpas.be/events/{id}/card-systems` answers 404. `workflowStatus` does not matter, registered ACC events exist in `READY_FOR_VALIDATION` and even `DELETED`.
- UiTPAS returns every card system linked to the organizer, each with an `enabled` flag. Disabling one keeps it in the response as `"enabled": false`. `RestUiTPASClient` filters those out today.
- Once UiTPAS registers the event, it gets its UiTPAS labels automatically. Only the organizer needed manual labels.
- Labels come from UDB3 config (`config.php` under `uitpas.labels`), keyed by card system id, so the label text can differ from the UiTPAS name. ACC card system 7 is "UiTPAS Oostende" but labels as `UiTPAS 7`.
- Card system ids differ between ACC and production. Do not copy them into non-ACC config.
