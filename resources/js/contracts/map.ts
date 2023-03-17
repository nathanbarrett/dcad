import * as jsts from 'jsts';
export interface Coordinate {
    lat: number;
    lng: number;
}

export class CustomArea {

    private polygon: google.maps.Polygon|null = null;

    constructor(public readonly coordinates: Coordinate[]) {
        if (this.coordinates.length < 3) {
            throw new Error('A polygon requires at least 3 coordinates');
        }
    }

    public static fromPolygon(polygon: google.maps.Polygon): CustomArea {
        // get all lat/lng coordinates from the polygon
        const coordinates: Coordinate[] = polygon.getPath().getArray().map((coordinate: google.maps.LatLng) => {
            return {
                lat: coordinate.lat(),
                lng: coordinate.lng(),
            };
        });

        const customArea = new CustomArea(coordinates);
        customArea.setPolygon(polygon);

        return customArea;
    }

    public setPolygon(polygon: google.maps.Polygon): void {
        this.polygon = polygon;
    }

    public getPolygon(): google.maps.Polygon {
        if (!this.polygon) {
            this.polygon = new google.maps.Polygon({
                paths: this.coordinates,
            });
        }

        return this.polygon;
    }

    public addToMap(map: google.maps.Map): void {
        this.getPolygon().setMap(map);
    }

    public removeFromMap(): void {
        this.getPolygon().setMap(null);
    }

    public overlaps(area: CustomArea): boolean {
        const intersection = this.getJsTsPolygon().intersection(area.getJsTsPolygon());

        return !intersection.isEmpty();
    }

    public getJsTsPolygon(): jsts.geom.Polygon {
        const factory = new jsts.geom.GeometryFactory();
        const coordinates = this.coordinates.map((coordinate: Coordinate) => new jsts.geom.Coordinate(coordinate.lng, coordinate.lat));
        coordinates.push(coordinates[0]);
        const linearRing = factory.createLinearRing(coordinates);
        return factory.createPolygon(linearRing);
    }
}
