import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ConnectionCheckPage } from './connection-check.page';
import { ConnectionStatus } from '../check-connection';

describe('ConnectionCheckPage', () => {
  let component: ConnectionCheckPage;
  let fixture: ComponentFixture<ConnectionCheckPage>;

  const status: ConnectionStatus = { reachable: true, detail: 'Backend conectado' };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ConnectionCheckPage],
      providers: [provideHttpClient(), provideHttpClientTesting()],
    }).compileComponents();

    fixture = TestBed.createComponent(ConnectionCheckPage);
    fixture.componentRef.setInput('connection', status);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('shows the connection detail', () => {
    expect(fixture.nativeElement.textContent).toContain('Backend conectado');
  });
});
